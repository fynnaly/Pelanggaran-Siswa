<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discipline_cases', function (Blueprint $table) {
            $table->foreignId('enrollment_id')->nullable()->after('student_id');
            $table->foreignId('class_id')->nullable()->after('student_id');
        });

        Schema::table('achievement_records', function (Blueprint $table) {
            $table->foreignId('enrollment_id')->nullable()->after('student_id');
            $table->foreignId('class_id')->nullable()->after('student_id');
        });
    }

    public function down(): void
    {
        Schema::table('discipline_cases', function (Blueprint $table) {
            $table->dropColumn(['enrollment_id', 'class_id']);
        });

        Schema::table('achievement_records', function (Blueprint $table) {
            $table->dropColumn(['enrollment_id', 'class_id']);
        });
    }
};
