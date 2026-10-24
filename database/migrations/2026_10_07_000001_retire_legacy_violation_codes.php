<?php

use App\Models\ViolationCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menghentikan kode pelanggaran warisan skema lama (A/B/C/D/E) dan menggantinya
 * dengan kode Pedoman Pancawaluya (P1-P5).
 *
 * Alasan: Pedoman Pendidikan Karakter Pancawaluya hanya mengenal lima kategori
 * pelanggaran berkode P1-P5, sehingga form kasus tidak boleh lagi menawarkan
 * kode a,b,c,d,e. Penghargaan memakai kode R1-R10 (sudah benar).
 *
 * Aman:
 * - Kasus yang masih menunjuk kategori warisan dipindahkan dulu ke sub P yang
 *   setara, baru baris warisan dihapus (foreign key discipline_cases memakai
 *   restrictOnDelete, jadi delete langsung akan gagal bila masih dipakai).
 * - redemption_rules ikut terhapus lewat cascadeOnDelete.
 * - Idempotent: kode yang sudah tidak ada dilewati begitu saja.
 */
return new class extends Migration
{
    /** Peta kode warisan → kode P pengganti (untuk kasus yang masih berjalan). */
    private const LEGACY_TO_P = [
        'B.15' => 'P1.1',   // Terlambat < 10 menit
        'B.16' => 'P1.2',   // Terlambat > 10 menit
        'B.17' => 'P1.3',   // Terlambat > 30 menit
        'C.01' => 'P2.3',   // Bolos / alpa tanpa keterangan
        'A.08' => 'P5.16',  // Melawan guru → melanggar norma
        'D.02' => 'P5.11',  // Merusak barang → vandalisme
        'E.05' => 'P5.1',   // Gawai di kelas
    ];

    /** Kode warisan yang dihentikan (induk lama tanpa pemetaan P). */
    private const LEGACY_ONLY = ['A', 'B', 'C', 'D'];

    public function up(): void
    {
        // 1. Pindahkan kasus yang masih menunjuk kategori warisan ke sub P setara.
        foreach (self::LEGACY_TO_P as $legacyCode => $pCode) {
            $targetId = DB::table('violation_categories')->where('code', $pCode)->value('id');
            if ($targetId === null) {
                continue; // sub P belum ada (seed belum jalan) — biarkan untuk run berikutnya
            }

            $legacyId = DB::table('violation_categories')->where('code', $legacyCode)->value('id');
            if ($legacyId === null) {
                continue;
            }

            DB::table('discipline_cases')
                ->where('violation_category_id', $legacyId)
                ->update(['violation_category_id' => $targetId]);
        }

        // 2. Hapus baris warisan. Induk lama (A-D) dihapus setelah sub-nya supaya
        //    nullOnDelete tidak menyisakan baris yatim yang ikut terhapus.
        $legacyCodes = array_merge(array_keys(self::LEGACY_TO_P), self::LEGACY_ONLY, ['TEST_1500', 'TEST_500']);

        $ids = DB::table('violation_categories')->whereIn('code', $legacyCodes)->pluck('id')->all();
        if ($ids === []) {
            return;
        }

        DB::table('violation_categories')->whereIn('parent_id', $ids)->update(['parent_id' => null]);
        ViolationCategory::whereIn('id', $ids)->delete();
    }

    public function down(): void
    {
        // Tidak ada rollback: kode warisan tidak dibuat ulang (data master
        // sekarang bersumber dari PancawaluyaSeeder). Jalankan db:seed untuk
        // memuat ulang P1-P5 / R1-R10.
    }
};
