<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jabatan guru (mis. Kepala Sekolah, Wks. Kesiswaan, Guru BK) dipisahkan
     * dari mata pelajaran. Spreadsheet sekolah hanya punya kolom
     * "JABATAN SEKARANG", yang sebelumnya keliru masuk ke mata_pelajaran.
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('jabatan', 150)->nullable()->after('mata_pelajaran');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('jabatan');
        });
    }
};
