<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Status aktif/nonaktif pada kelas untuk riwayat per tahun ajaran.
 *
 * Cara baca guru: murid A di kelas A tahun 2024 (status kelas aktif saat itu),
 * naik tahun ajaran → PromotionService membuat kelas baru, kelas lama
 * ditandai nonaktif agar jelas itu riwayat, bukan kelas berjalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (! Schema::hasColumn('classes', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('capacity');
            }
        });

        $activeYearId = DB::table('academic_years')->where('is_active', true)->value('id');

        if ($activeYearId) {
            DB::table('classes')
                ->where('academic_year_id', '!=', $activeYearId)
                ->where('status', 'active')
                ->update(['status' => 'inactive']);
        }
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
