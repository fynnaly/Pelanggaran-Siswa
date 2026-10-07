<?php

namespace Database\Seeders;

use App\Models\AchievementCategory;
use App\Models\RedemptionRule;
use App\Models\ViolationCategory;
use Illuminate\Database\Seeder;

/**
 * Hierarki kategori pelanggaran (induk → sub) + sub-prestasi padanan
 * beserta pemetaan redemption_rules.
 *
 * Sumber data: dokumen kesiswaan (point-scoring-rules.md) — 16 sub-prestasi
 * padanan dan pemetaannya ke kode pelanggaran.
 *
 * Idempotent: firstOrCreate + updateOrCreate, aman dijalankan berulang.
 */
class ViolationHierarchySeeder extends Seeder
{
    /** Kategori induk: kode → [nama, group_type]. */
    protected const PARENTS = [
        'A' => ['Penampilan', 'penampilan'],
        'B' => ['Sikap & Tindakan', 'sikap'],
        'C' => ['Kehadiran', 'kehadiran'],
        'D' => ['Perilaku', 'perilaku'],
    ];

    /** Sub-prestasi padanan: kode → [nama, poin, group_type]. */
    protected const ACHIEVEMENTS = [
        '1.3' => ['Praktik keagamaan', 20, 'keagamaan'],
        '2.1' => ['Melaporkan barang temuan', 10, 'karakter'],
        '2.2' => ['Berkata jujur dalam kesaksian', 10, 'karakter'],
        '2.3' => ['Melaporkan pelanggaran orang lain', 20, 'karakter'],
        '2.4' => ['Jujur dalam asesmen', 5, 'karakter'],
        '3.4' => ['Aktif dalam kegiatan belajar', 10, 'akademik'],
        '3.5' => ['Menghasilkan karya inovatif', 20, 'akademik'],
        '4.3' => ['Tidak melanggar tata tertib 3 bulan', 20, 'karakter'],
        '4.4' => ['Tidak melanggar tata tertib 6 bulan', 30, 'karakter'],
        '5.2' => ['Menolong orang kena musibah', 10, 'sosial'],
        '5.3' => ['Bergaul sesuai etika dan norma', 10, 'sosial'],
        '7.1' => ['Pendidikan Kesadaran Bela Negara', 10, 'kepemimpinan'],
        '7.7' => ['Petugas upacara', 10, 'kepemimpinan'],
        '8.1' => ['Aktif dalam ekskul wajib', 10, 'nonakademik'],
        '9.1' => ['Merawat lingkungan sekolah', 5, 'kebersihan'],
        '9.2' => ['Membuang dan memilah sampah (per minggu)', 10, 'kebersihan'],
    ];

    /**
     * Pemetaan sub-kategori → sub-prestasi padanan.
     * Key = kode pelanggaran, value = daftar kode padanan.
     * Kode TEST_* dan zero-tolerance sengaja tidak dipetakan.
     */
    protected const REDEMPTIONS = [
        'B.15' => ['7.1', '4.3'],           // Terlambat < 10 menit
        'B.16' => ['7.1', '4.3'],           // Terlambat > 10 menit
        'B.17' => ['7.1', '4.3'],           // Terlambat > 30 menit
        'A.08' => ['5.3', '4.3'],           // Melawan guru → etika + tata tertib
        'C.01' => ['4.3'],                  // Bolos sekolah → tata tertib 3 bulan
        'E.05' => ['3.4'],                  // HP di kelas → aktif kegiatan belajar
        'A.10' => ['4.3'],                  // Seragam tidak sesuai jadwal (dokumen)
        'A.17' => ['9.1'],                  // Mencoret seragam → lingkungan (dokumen)
        'B.1'  => ['9.1'],                  // Tidak piket → merawat lingkungan
        'B.2'  => ['9.1'],                  // Kelas kotor
        'B.7'  => ['9.1'],                  // Vandalisme
        'B.19' => ['7.7'],                  // Tidak ikut upacara → petugas upacara
        'B.20' => ['8.1'],                  // Tidak ikut ekskul
        'B.26' => [],                       // Bullying = zero tolerance, tanpa penebusan
    ];

    public function run(): void
    {
        // 1. Kategori induk
        $parents = [];
        foreach (self::PARENTS as $code => [$name, $groupType]) {
            $parents[$code] = ViolationCategory::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'group_type' => $groupType,
                    'severity' => 'ringan',
                    'points' => 0,
                    'description' => "Kategori induk {$name}. Sub-kategori di bawahnya yang dipilih saat membuat kasus.",
                    'status' => 'active',
                ]
            );
        }

        // 2. Sub-prestasi padanan (achievement_categories)
        $achievements = [];
        foreach (self::ACHIEVEMENTS as $code => [$name, $points, $groupType]) {
            $achievements[$code] = AchievementCategory::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'points' => $points,
                    'group_type' => $groupType,
                    'description' => 'Sub-prestasi padanan penebusan poin (dokumen kesiswaan).',
                    'status' => 'active',
                ]
            );
        }

        // 3. Sub-kategori aktif → pasangkan induk sesuai group_type
        $groupToParent = [
            'penampilan' => 'A',
            'sikap' => 'B',
            'kehadiran' => 'C',
            'perilaku' => 'D',
        ];
        ViolationCategory::whereNull('parent_id')
            ->where('status', 'active')
            ->whereNotIn('code', array_keys(self::PARENTS))
            ->get()
            ->each(function ($cat) use ($groupToParent, $parents) {
                $parentCode = $groupToParent[$cat->group_type] ?? null;
                if ($parentCode !== null && isset($parents[$parentCode])) {
                    $cat->update(['parent_id' => $parents[$parentCode]->id]);
                }
            });

        // 3.5. Isi sanksi bawaan per tingkat untuk sub-kategori yang belum punya.
        ViolationCategory::whereNotNull('parent_id')
            ->whereNull('sanksi')
            ->get()
            ->each(function ($cat) {
                $sanksi = $cat->is_zero_tolerance
                    ? 'Program Pembinaan Intensif — rapat dewan guru, tidak bisa ditebus poin prestasi'
                    : match ($cat->severity) {
                        'ringan' => 'Teguran lisan + pembinaan wali kelas',
                        'sedang' => 'Teguran tertulis + surat pernyataan orang tua',
                        'berat' => 'Pemanggilan orang tua + teguran tertulis',
                    };
                $cat->update(['sanksi' => $sanksi]);
            });

        // 4. Redemption rules — pemetaan padanan per dokumen
        foreach (self::REDEMPTIONS as $violationCode => $achCodes) {
            $violation = ViolationCategory::where('code', $violationCode)->first();
            if (! $violation) {
                continue;
            }
            foreach ($achCodes as $achCode) {
                $ach = $achievements[$achCode] ?? null;
                if (! $ach) {
                    continue;
                }
                RedemptionRule::updateOrCreate(
                    [
                        'violation_category_id' => $violation->id,
                        'achievement_category_id' => $ach->id,
                    ],
                    ['poin_penebus' => $ach->points]
                );
            }
        }
    }
}
