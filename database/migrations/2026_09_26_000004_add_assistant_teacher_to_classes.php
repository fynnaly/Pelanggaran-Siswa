<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('classes') && ! Schema::hasColumn('classes', 'assistant_teacher_id')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->foreignId('assistant_teacher_id')
                    ->nullable()
                    ->after('homeroom_teacher_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('classes', 'assistant_teacher_id')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('assistant_teacher_id');
            });
        }
    }
};
