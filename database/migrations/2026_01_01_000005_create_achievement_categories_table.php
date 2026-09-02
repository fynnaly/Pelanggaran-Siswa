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
        Schema::create('achievement_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // ACH-01, PENCAPAIAN-01, PENGHARGAAN-01, PHG-01, dst
            $table->string('name', 150);
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
        Schema::dropIfExists('achievement_categories');
    }
};
