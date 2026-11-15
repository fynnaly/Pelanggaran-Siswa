<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('deletion_approved_at')->nullable();
            $table->foreignId('deletion_approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['deletion_approved_by']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['is_active', 'deletion_approved_at', 'deletion_approved_by']);
        });
    }
};
