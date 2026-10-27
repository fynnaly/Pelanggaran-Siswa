<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $cols = array_values(array_filter(['class_id', 'academic_year_id'], fn ($col) => Schema::hasColumn('students', $col)));
            if (! empty($cols)) {
                Schema::disableForeignKeyConstraints();
                Schema::table('students', function ($table) use ($cols) {
                    $table->dropColumn($cols);
                });
                Schema::enableForeignKeyConstraints();
            }
            return;
        }

        $foreignKeys = collect(DB::select(
            'SELECT CONSTRAINT_NAME name FROM information_schema.TABLE_CONSTRAINTS '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = ?',
            ['students', 'FOREIGN KEY']
        ))->map->name->all();

        $indexes = collect(DB::select(
            'SELECT DISTINCT INDEX_NAME name FROM information_schema.STATISTICS '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND NON_UNIQUE = 1',
            ['students', 'class_id']
        ))->map->name->all();

        foreach ($foreignKeys as $name) {
            if (str_contains($name, 'class_id') || str_contains($name, 'academic_year_id')) {
                DB::statement("ALTER TABLE `students` DROP FOREIGN KEY `$name`");
            }
        }

        foreach (['class_id', 'academic_year_id'] as $column) {
            if (! Schema::hasColumn('students', $column)) {
                continue;
            }

            $columnIndexes = collect(DB::select(
                'SELECT DISTINCT INDEX_NAME name FROM information_schema.STATISTICS '
                . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['students', $column]
            ))->map->name->reject(fn ($n) => $n === 'PRIMARY')->all();

            foreach ($columnIndexes as $index) {
                DB::statement("ALTER TABLE `students` DROP INDEX `$index`");
            }

            DB::statement("ALTER TABLE `students` DROP COLUMN `$column`");
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->after('status')->constrained('classes');
            $table->foreignId('academic_year_id')->nullable()->after('class_id')->constrained('academic_years');
        });
    }
};
