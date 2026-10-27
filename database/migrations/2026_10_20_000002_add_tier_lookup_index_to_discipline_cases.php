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
        Schema::table('discipline_cases', function (Blueprint $table) {
            $table->index(
                ['student_id', 'violation_category_id', 'academic_year_id', 'status'],
                'discipline_cases_tier_lookup_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discipline_cases', function (Blueprint $table) {
            $table->dropIndex('discipline_cases_tier_lookup_index');
        });
    }
};
