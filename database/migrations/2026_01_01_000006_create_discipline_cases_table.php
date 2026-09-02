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
        Schema::create('discipline_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number', 30)->unique(); //KAS-2026-0001, CASE-2026-0001, KASUS-2026-0001
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('violation_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('report_by')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['found', 'validated', 'dismissed', 'done'])->default('found');
            $table->string('location', 100)->nullable();
            $table->text('description');
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discipline_cases');
    }
};
