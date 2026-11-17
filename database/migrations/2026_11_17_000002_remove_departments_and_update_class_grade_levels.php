<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Peta tingkat lama (SMA) ke tingkat baru (SMP). */
    private const GRADE_MAP = [
        'X' => 'VII',
        'XI' => 'VIII',
        'XII' => 'IX',
        '7' => 'VII',
        '8' => 'VIII',
        '9' => 'IX',
        'VII' => 'VII',
        'VIII' => 'VIII',
        'IX' => 'IX',
    ];

    /**
     * Hapus konsep jurusan dan ubah tingkat kelas menjadi VII/VIII/IX.
     *
     * Nama kelas lama seperti "X PPLG 1" dirapikan menjadi "VII 1". Bagian
     * rombel diambil dari token terakhir nama lama (angka atau huruf).
     */
    public function up(): void
    {
        // Longgarkan dulu enum lama (MySQL) / check constraint (SQLite) agar
        // data tingkat baru bisa ditulis sebelum enum baru dipasang.
        Schema::table('classes', function (Blueprint $table) {
            $table->string('grade_level', 10)->nullable()->change();
        });

        $this->migrateClassNames();

        Schema::table('classes', function (Blueprint $table) {
            $table->enum('grade_level', ['VII', 'VIII', 'IX'])->nullable()->change();
        });

        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }

            if (Schema::hasColumn('classes', 'major')) {
                $table->dropColumn('major');
            }
        });

        Schema::dropIfExists('departments');
    }

    /** Kembalikan struktur lama (data nama kelas tidak bisa dipulihkan sepenuhnya). */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->string('grade_level', 10)->nullable()->change();
        });

        $this->restoreOldGradeLevels();

        Schema::table('classes', function (Blueprint $table) {
            $table->enum('grade_level', ['X', 'XI', 'XII'])->nullable()->change();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('academic_year_id')
                ->constrained('departments')
                ->nullOnDelete();
            $table->string('major', 50)->nullable()->after('grade_level');
        });
    }

    private function migrateClassNames(): void
    {
        $classes = DB::table('classes')->select('id', 'academic_year_id', 'name', 'grade_level')->get();
        $used = [];

        foreach ($classes as $class) {
            $tokens = preg_split('/[\s\-]+/', trim((string) $class->name)) ?: [];

            $grade = self::GRADE_MAP[strtoupper(trim((string) $class->grade_level))] ?? null;
            $grade ??= self::GRADE_MAP[strtoupper($tokens[0] ?? '')] ?? 'VII';

            $rombel = count($tokens) > 1 ? (string) end($tokens) : '1';
            $rombel = strtoupper(trim($rombel)) ?: '1';

            $name = "{$grade} {$rombel}";
            $attempt = 0;

            // Nama kelas unik per tahun ajaran. Jika dua jurusan lama punya
            // nomor rombel sama, beri akhiran huruf agar tetap unik.
            while (isset($used[$class->academic_year_id][$name])) {
                $attempt++;
                $suffix = $attempt <= 26 ? chr(64 + $attempt) : (string) $attempt;
                $name = "{$grade} ".substr($rombel, 0, 1).$suffix;
            }

            $used[$class->academic_year_id][$name] = true;

            DB::table('classes')->where('id', $class->id)->update([
                'name' => $name,
                'grade_level' => $grade,
            ]);
        }
    }

    private function restoreOldGradeLevels(): void
    {
        $map = ['VII' => 'X', 'VIII' => 'XI', 'IX' => 'XII'];

        foreach (DB::table('classes')->select('id', 'name', 'grade_level')->get() as $class) {
            $grade = $map[$class->grade_level] ?? 'X';
            $parts = preg_split('/[\s\-]+/', trim((string) $class->name)) ?: [];
            $parts[0] = $grade;

            DB::table('classes')->where('id', $class->id)->update([
                'name' => implode(' ', $parts),
                'grade_level' => $grade,
            ]);
        }
    }
};
