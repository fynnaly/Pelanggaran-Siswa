<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
        });

        // Seed & backfill jurusan awal berdasarkan major yang sudah ada di classes
        $now = now();
        $defaults = [
            [
                'code' => 'RPL',
                'name' => 'Rekayasa Perangkat Lunak',
                'description' => 'Konsentrasi keahlian pengembangan aplikasi web, mobile, dan sistem komputasi.',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'TKJ',
                'name' => 'Teknik Komputer dan Jaringan',
                'description' => 'Konsentrasi keahlian infrastruktur jaringan, administrasi server, dan sistem operasi.',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'AKT',
                'name' => 'Akuntansi dan Keuangan Lembaga',
                'description' => 'Konsentrasi keahlian pencatatan keuangan, perpajakan, dan pelaporan akuntansi digital.',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($defaults as $dept) {
            $id = DB::table('departments')->insertGetId($dept);
            // Hubungkan ke kelas yang major-nya sama
            DB::table('classes')
                ->where('major', $dept['code'])
                ->update(['department_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });

        Schema::dropIfExists('departments');
    }
};
