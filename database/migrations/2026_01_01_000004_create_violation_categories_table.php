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
        Schema::create('violation_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); //ABC-01, VLT-01, PLGR-01, LANGGAR-01
            $table->string('name', 150);
            $table->enum('severity', ['ringan', 'sedang', 'berat'])->default('ringan');
            // * disimpan secara positif, arah debit akan ditentukan ledger
            $table->unsignedInteger('points');
            $table->enum('status', ['draft', 'active'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('violation_categories');
    }
};
