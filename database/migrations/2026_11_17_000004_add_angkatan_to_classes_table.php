<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Angkatan = tahun masuk siswa pada tingkatan tersebut.
     * VII = tahun awal tahun ajaran, VIII = tahun awal - 1, IX = tahun awal - 2.
     */
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->unsignedSmallInteger('angkatan')->nullable()->after('grade_level')->index();
        });

        $rows = DB::table('classes')
            ->join('academic_years', 'academic_years.id', '=', 'classes.academic_year_id')
            ->get(['classes.id', 'classes.grade_level', 'academic_years.name', 'academic_years.start_date']);

        foreach ($rows as $row) {
            $startYear = null;

            if ($row->start_date) {
                $startYear = (int) substr((string) $row->start_date, 0, 4);
            } elseif (preg_match('/(\d{4})/', (string) $row->name, $matches)) {
                $startYear = (int) $matches[1];
            }

            if (! $startYear) {
                continue;
            }

            $offset = match (strtoupper((string) $row->grade_level)) {
                'VIII' => 1,
                'IX' => 2,
                default => 0,
            };

            DB::table('classes')->where('id', $row->id)->update([
                'angkatan' => $startYear - $offset,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropIndex(['angkatan']);
            $table->dropColumn('angkatan');
        });
    }
};
