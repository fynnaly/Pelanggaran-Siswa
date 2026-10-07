<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hierarki kategori pelanggaran + rekomendasi pemulihan otomatis.
 *
 * - violation_categories.parent_id: sub-kategori menunjuk kategori induk (self FK).
 * - violation_categories.sanksi: teks sanksi bawaan dari dokumen kesiswaan.
 * - redemption_rules: pemetaan sub-kategori → sub-prestasi padanan (poin penebus).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violation_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('violation_categories', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('group_type')
                    ->constrained('violation_categories')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('violation_categories', 'sanksi')) {
                $table->string('sanksi', 255)->nullable()->after('description');
            }
        });

        if (! Schema::hasTable('redemption_rules')) {
            Schema::create('redemption_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('violation_category_id')->constrained('violation_categories')->cascadeOnDelete();
                $table->foreignId('achievement_category_id')->constrained('achievement_categories')->cascadeOnDelete();
                $table->unsignedInteger('poin_penebus');
                $table->timestamps();

                $table->unique(
                    ['violation_category_id', 'achievement_category_id'],
                    'redemption_violation_achievement_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('redemption_rules');

        Schema::table('violation_categories', function (Blueprint $table) {
            if (Schema::hasColumn('violation_categories', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }
            if (Schema::hasColumn('violation_categories', 'sanksi')) {
                $table->dropColumn('sanksi');
            }
        });
    }
};
