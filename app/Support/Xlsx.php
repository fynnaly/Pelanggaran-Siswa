<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/**
 * Baca/tulis XLSX minimal tanpa library eksternal.
 *
 * XLSX = ZIP berisi XML. Kita hanya butuh bagian wajib OPC:
 * [Content_Types].xml, _rels/.rels, xl/workbook.xml,
 * xl/_rels/workbook.xml.rels, xl/worksheets/sheet1.xml.
 *
 * Sengaja tidak memakai PhpSpreadsheet/Maatwebsite: keduanya mewajibkan
 * ext-gd (lihat "require" packagist), sementara runtime produksi memakai
 * PHP 8.5.5 tanpa ext-gd. Ekstensi yang dipakai di sini (zip + xml + dom)
 * tersedia di semua build PHP standar.
 *
 * Pembacaan memakai DOMDocument + XPath, bukan SimpleXML: atribut sel
 * seperti r="A1" tidak ber-namespace sementara elemennya ber-namespace.
 * Kombinasi itu membuat SimpleXML children(NS) gagal pada file yang
 * ditulis Excel/LibreOffice asli (r dan t ikut terbaca kosong).
 */
class Xlsx
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /**
     * Baca sheet pertama menjadi array baris (array of array of string).
     * Mendukung shared string (plain + rich text), inline string, dan angka.
     */
    public static function read(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File bukan XLSX yang valid (gagal dibuka sebagai ZIP).');
        }

        try {
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheetXml === false) {
                throw new RuntimeException('Sheet1 tidak ditemukan di dalam file XLSX.');
            }

            $shared = self::sharedStrings($zip);

            $dom = new DOMDocument;
            if (! @$dom->loadXML($sheetXml)) {
                throw new RuntimeException('XML sheet tidak valid.');
            }
            $xp = new DOMXPath($dom);
            $xp->registerNamespace('m', self::NS);

            $rows = [];
            foreach ($xp->query('//m:sheetData/m:row') as $row) {
                /** @var \DOMElement $row */
                $cells = [];
                foreach ($xp->query('m:c', $row) as $c) {
                    /** @var \DOMElement $c */
                    $col  = self::columnIndex($c->getAttribute('r'));
                    $type = $c->getAttribute('t');

                    $cells[$col] = match ($type) {
                        's' => $shared[(int) $xp->evaluate('string(m:v)', $c)] ?? '',
                        'inlineStr' => $xp->evaluate('string(m:is/m:t)', $c),
                        default => self::normalizeNumber($xp->evaluate('string(m:v)', $c)),
                    };
                }

                if ($cells === []) {
                    continue;
                }
                $max = max(array_keys($cells));
                $rows[] = array_map(fn ($i) => $cells[$i] ?? '', range(0, $max));
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    /**
     * Tulis array baris ke file XLSX (inline string — tanpa sharedStrings).
     * $styleHeader = true mengecat baris pertama (tebal, teks putih, latar biru).
     * Mengembalikan path file sementara; pemanggil yang bertugas menghapusnya.
     */
    public static function write(array $rows, string $sheetName = 'template-siswa', bool $styleHeader = false): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx_') . '.xlsx';

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat file XLSX.');
        }

        try {
            $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . ($styleHeader
                    ? '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                    : '')
                . '</Types>';
            $zip->addFromString('[Content_Types].xml', $contentTypes);

            $zip->addFromString('_rels/.rels',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>');

            $zip->addFromString('xl/workbook.xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
                . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheets><sheet name="' . self::esc($sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
                . '</workbook>');

            $zip->addFromString('xl/_rels/workbook.xml.rels',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                . ($styleHeader
                    ? '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                    : '')
                . '</Relationships>');

            if ($styleHeader) {
                $zip->addFromString('xl/styles.xml', self::stylesXml());
            }

            $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="' . self::NS . '"><sheetData>';

            foreach (array_values($rows) as $r => $row) {
                $num = $r + 1;
                $styleAttr = ($styleHeader && $r === 0) ? ' s="1"' : '';
                $sheet .= '<row r="' . $num . '">';
                foreach (array_values($row) as $c => $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }
                    $ref = self::columnLetter($c) . $num;
                    $sheet .= '<c r="' . $ref . '"' . $styleAttr . ' t="inlineStr"><is><t xml:space="preserve">'
                        . self::esc((string) $value) . '</t></is></c>';
                }
                $sheet .= '</row>';
            }

            $zip->addFromString('xl/worksheets/sheet1.xml', $sheet . '</sheetData></worksheet>');
        } finally {
            $zip->close();
        }

        return $path;
    }

    /**
     * Stylesheet minimal: fontId 1 = tebal putih, fillId 2 = latar biru solid.
     * cellXfs[1] = gaya header (dipakai lewat atribut s="1").
     * Dua fill pertama (none + gray125) wajib menurut spesifikasi ECMA-376.
     */
    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1F4E79"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"'
            . ' applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    /** Ambil sharedStrings.xml (opsional) menjadi array index → teks. */
    private static function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $dom = new DOMDocument;
        if (! @$dom->loadXML($xml)) {
            return [];
        }
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('m', self::NS);

        $out = [];
        foreach ($xp->query('//m:si') as $si) {
            // Rich text: beberapa <r><t>. Plain text: satu <t> langsung.
            $rich = $xp->query('m:r/m:t', $si);
            if ($rich->length > 0) {
                $text = '';
                foreach ($rich as $t) {
                    $text .= $t->textContent;
                }
            } else {
                $text = $xp->evaluate('string(m:t)', $si);
            }
            $out[] = $text;
        }

        return $out;
    }

    /**
     * Normalisasi nilai numerik sel.
     * Excel menyimpan digit >11 sebagai float ilmiah ("1.23456789E+11");
     * NISN/NIS wajib dipulihkan ke digit penuh, bukan notasi ilmiah.
     * Presisi ganda Excel aman sampai 15 digit.
     */
    private static function normalizeNumber(string $value): string
    {
        $value = trim($value);
        if ($value === '' || ! is_numeric($value)) {
            return $value;
        }
        if (! str_contains($value, '.') && stripos($value, 'e') === false) {
            return $value;
        }

        return number_format((float) $value, 0, '', '');
    }

    /** "A1" → 0, "B1" → 1, "AA1" → 26. Nama kolom berubah jadi index. */
    private static function columnIndex(string $ref): int
    {
        preg_match('/^([A-Z]+)/', $ref, $m);
        $letters = $m[1] ?? 'A';

        $index = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    /** 0 → A, 25 → Z, 26 → AA. */
    private static function columnLetter(int $index): string
    {
        $letters = '';
        $index++;
        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intdiv($index, 26);
        }

        return $letters;
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
