<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kaitkan rekam prestasi ke kasus pelanggaran yang ditebusnya.
     *
     * Kolom nullable: prestasi biasa (bukan penebusan) tetap boleh tanpa kasus.
     * Saat diisi, prestasi ini dianggap "sanksi sudah dijalani + pemulihan poin"
     * untuk kasus tersebut.
     */
    public function up(): void
    {
        Schema::table('achievement_records', function (Blueprint $table) {
            $table->foreignId('discipline_case_id')
                ->nullable()
                ->after('achievement_category_id')
                ->constrained('discipline_cases')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('achievement_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discipline_case_id');
        });
    }
};
