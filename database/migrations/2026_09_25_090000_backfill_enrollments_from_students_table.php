<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'class_id') || ! Schema::hasColumn('students', 'academic_year_id')) {
            return;
        }

        $students = DB::table('students')
            ->whereNotNull('class_id')
            ->whereNotNull('academic_year_id')
            ->get();

        foreach ($students as $s) {
            $exists = DB::table('enrollments')
                ->where('student_id', $s->id)
                ->where('academic_year_id', $s->academic_year_id)
                ->exists();

            if (!$exists) {
                DB::table('enrollments')->insert([
                    'student_id' => $s->id,
                    'class_id' => $s->class_id,
                    'academic_year_id' => $s->academic_year_id,
                    'status' => 'active',
                    'enrolled_at' => $s->created_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('enrollments')->truncate();
    }
};
