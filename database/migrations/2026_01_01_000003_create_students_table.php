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
            if (DB::getDriverName() === 'sqlite') {
                $table->unsignedBigInteger('class_id')->nullable();
            } else {
                $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            }
            $table->enum('gender', ['L', 'P'])->default('L');
            $table->date('birth_date')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('parent_name', 255)->nullable();
            $table->string('parent_phone', 20)->nullable();
            $table->string('nisn', 20)->unique();
            $table->string('nis', 20)->unique();
            $table->string('full_name');
            $table->string('avatar_path')->nullable();
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
