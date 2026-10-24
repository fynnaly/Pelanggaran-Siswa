<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hierarki kategori prestasi (induk R1-R10 → sub R1.1-R10.5).
 *
 * Meniru pola violation_categories.parent_id: sub menunjuk induk (self FK),
 * baris tanpa parent_id = kategori induk. Kategori induk tidak dipilih di
 * form rekam prestasi — hanya sub yang dipakai sebagai kandidat poin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievement_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('achievement_categories', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('group_type')
                    ->constrained('achievement_categories')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('achievement_categories', function (Blueprint $table) {
            if (Schema::hasColumn('achievement_categories', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }
        });
    }
};
