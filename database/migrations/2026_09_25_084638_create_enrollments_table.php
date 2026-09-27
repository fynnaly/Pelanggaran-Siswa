<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'completed', 'graduated', 'withdrawn', 'transferred'])->default('active');
            $table->date('enrolled_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id'], 'enrollments_student_year_unique');
            $table->index(['academic_year_id', 'class_id']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
