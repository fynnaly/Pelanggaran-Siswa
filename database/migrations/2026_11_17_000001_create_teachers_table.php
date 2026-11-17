<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master data guru/staf pengajar.
     *
     * `users.nip` sudah ada sejak lama; tabel ini menjadikan guru sebagai
     * data referensi tersendiri (NIP, nama, mata pelajaran) yang bisa
     * ditautkan ke akun pengguna seperti pola `students.user_id`.
     */
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('nip', 40)->nullable()->unique();
            $table->string('full_name');
            $table->string('mata_pelajaran', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('avatar_path')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Backfill: akun guru/wali kelas yang sudah ada otomatis menjadi data guru.
        $roleIds = DB::table('roles')->whereIn('name', ['guru', 'wali_kelas'])->pluck('id');

        if ($roleIds->isEmpty()) {
            return;
        }

        $userIds = DB::table('model_has_roles')
            ->whereIn('role_id', $roleIds)
            ->where('model_type', (new User)->getMorphClass())
            ->distinct()
            ->pluck('model_id');

        if ($userIds->isEmpty()) {
            return;
        }

        $now = now();
        $rows = [];
        $seenNips = [];

        foreach (DB::table('users')->whereIn('id', $userIds)->whereNull('deleted_at')->get(['id', 'name', 'nip']) as $user) {
            $nip = $user->nip ?: null;

            // NIP unik per guru; akun lama yang kebetulan berbagi NIP tetap
            // di-backfill tanpa NIP agar migrasi tidak gagal.
            if ($nip !== null && isset($seenNips[$nip])) {
                $nip = null;
            }
            if ($nip !== null) {
                $seenNips[$nip] = true;
            }

            $rows[] = [
                'user_id' => $user->id,
                'nip' => $nip,
                'full_name' => $user->name,
                'mata_pelajaran' => null,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('teachers')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
