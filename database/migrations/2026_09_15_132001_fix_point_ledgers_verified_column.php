<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign key constraints first
        Schema::table('point_ledgers', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropForeign(['verified_at']);
        });

        // Fix: verified_by harus nullable +verified_at harus timestamp, bukan foreignId
        DB::statement('ALTER TABLE point_ledgers MODIFY verified_by BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE point_ledgers MODIFY verified_at TIMESTAMP NULL');
    }

    public function down(): void
    {
        // Revert ke bentuk semula
        DB::statement('ALTER TABLE point_ledgers MODIFY verified_by BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE point_ledgers MODIFY verified_at BIGINT UNSIGNED NULL');

        Schema::table('point_ledgers', function (Blueprint $table) {
            $table->foreign('verified_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('verified_at')->references('id')->on('users')->nullOnDelete();
        });
    }
};
