<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminSeeder extends Seeder
{
    /**
     * Jalankan seeder khusus akun admin dan role dasar.
     * Tidak menyertakan data referensi (Tahun Ajaran, Kelas, Siswa, Kategori)
     * untuk keperluan pengujian kondisi data awal / guard prasyarat kosong.
     */
    public function run(): void
    {
        // Reset cache permission Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat seluruh role sistem dasar
        foreach (['admin', 'guru', 'wali_kelas', 'bk', 'siswa', 'kesiswaan', 'kepala_sekolah'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Buat akun Admin tunggal
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'nip' => '198001012005011001',
            ]
        );

        $admin->syncRoles(['admin']);
    }
}
