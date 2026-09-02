<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('point_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            // * Nambah atau berkurang
            $table->enum('direction', ['credit', 'debit']);
            $table->unsignedInteger('amount');
            $table->integer('balance_after');
            $table->enum('transaction_type', ['OPENING_BALANCE', 'ACHIEVEMENT', 'VIOLATION', 'RECOVERY', 'REVERSAL']);
            $table->nullableMorphs('source');
            $table->string('reason')->nullable();
            $table->foreignId('verified_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('verified_at')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'academic_year_id']);
        });

        DB::statement("
            CREATE UNIQUE INDEX point_ledgers_one_opening_per_year ON point_ledgers (student_id, academic_year_id) WHERE transaction_type = 'OPENING_BALANCE'
        ");

        DB::statement("
            CREATE UNIQUE INDEX point_ledgers_unique_source ON point_ledgers (source_type, source_id) WHERE source_type IS NOT NULL AND transaction_type IN ('VIOLATION', 'ACHIEVEMENT')
        ");
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_ledgers');
        DB::statement('DROP INDEX IF EXISTS point_ledgers_one_opening_per_year');
        DB::statement('DROP INDEX IF EXISTS point_ledgers_unique_source');
    }
};
