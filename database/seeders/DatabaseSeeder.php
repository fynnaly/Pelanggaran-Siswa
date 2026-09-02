<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /* ---------------------------------- ROLES --------------------------------- */
        foreach (['admin', 'guru', 'bk', 'siswa'] as $peran) {
            Role::firstOrCreate(['name' => $peran, 'guard_name' => 'web']);
        }

        /* ---------------------------------- USER ---------------------------------- */
        $admin = User::firstOrCreate(['email' => 'admin@admin.com'], ['name' => 'Admin', 'password' => Hash::make('password')]);
        $admin->syncRoles(['admin']);

        $guru = User::firstOrCreate(['email' => 'guru@guru.com'], ['name' => 'Guru', 'password' => Hash::make('password')]);
        $guru->syncRoles(['guru']);

        $bk = User::firstOrCreate(['email' => 'bk@bk.com'], ['name' => 'BK', 'password' => Hash::make('password')]);
        $bk->syncRoles(['bk']);

        $wali = User::firstOrCreate(['email' => 'wali@wali.com'], ['name' => 'Wali', 'password' => Hash::make('password')]);
        $wali->syncRoles(['guru']);

        $siswa1 = User::firstOrCreate(['email' => 'siswa1@siswa.com'], ['name' => 'Siswa 1', 'password' => Hash::make('password')]);
        $siswa2 = User::firstOrCreate(['email' => 'siswa2@siswa.com'], ['name' => 'Siswa 2', 'password' => Hash::make('password')]);
        foreach ([$siswa1, $siswa2] as $user) { $user->syncRoles(['siswa']); }
    }
}
