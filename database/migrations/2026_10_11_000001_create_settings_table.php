<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengaturan global sekolah & aplikasi (key-value).
     *
     * key dipakai kode: app_name, school_name, school_address, school_phone,
     * kop_pemerintah, kop_dinas, signer_kepala_nama, signer_kepala_nip,
     * signer_kesiswaan_nama, signer_kesiswaan_nip, signer_ceriticate_date,
     * ttd_kepala_path, ttd_kesiswaan_path, logo_path.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
