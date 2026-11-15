<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class CategorySpreadsheet
{
    /**
     * Read XLSX or delimited text rows, including the header row.
     *
     * @return array<int, array<int, string>>
     */
    public static function read(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === 'xlsx') {
            return Xlsx::read($file->getRealPath());
        }

        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            throw new RuntimeException('Berkas tidak dapat dibaca.');
        }

        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $firstLine = strtok($contents, "\r\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException('Gagal menyiapkan pembacaan berkas.');
        }

        fwrite($stream, $contents);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream, 0, $delimiter)) !== false) {
            $rows[] = array_map(fn ($value) => (string) $value, $row);
        }
        fclose($stream);

        return $rows;
    }

    /**
     * Normalize column names for comparisons.
     *
     * @param  array<int, mixed>  $headers
     * @return array<int, string>
     */
    public static function normalizeHeaders(array $headers): array
    {
        return array_map(
            fn ($header) => mb_strtolower(trim((string) $header)),
            $headers,
        );
    }
}
