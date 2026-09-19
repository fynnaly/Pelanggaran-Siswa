<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the uniq_opening_balance unique index from point_ledgers.
     *
     * This index uniquely constrains (student_id, academic_year_id, transaction_type),
     * which was intended for OPENING_BALANCE dedup but incorrectly blocks multiple
     * VIOLATION entries per student per year. The ledger_idempotency constraint
     * (student_id, academic_year_id, transaction_type, source_id) already handles
     * per-source deduplication correctly.
     */
    public function up(): void
    {
        Schema::connection(config('database.default'))->table('point_ledgers', function ($table) {
            $table->dropUnique('uniq_opening_balance');
        });
    }

    public function down(): void
    {
        Schema::connection(config('database.default'))->table('point_ledgers', function ($table) {
            $table->unique(['student_id', 'academic_year_id', 'transaction_type'], 'uniq_opening_balance');
        });
    }
};
