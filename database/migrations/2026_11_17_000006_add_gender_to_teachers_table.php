<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Jenis kelamin guru (L/P) — mengikuti kolom L/P pada spreadsheet sekolah. */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('gender', 1)->nullable()->after('full_name');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
