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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            // Akun login
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            $table->string('nisn', 20)->unique();
            $table->string('nis', 20)->unique();
            $table->string('full_name');
            $table->string('username', 20)->unique()->nullable(); // yang akan tampil di leaderboard
            $table->string('avatar_path')->nullable();
            $table->boolean('is_leaderboard_visible')->default(true);
            $table->enum('status', ['active', 'inactive', 'graduated', 'transferred'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
