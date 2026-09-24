<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\ViolationCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /** Seed the application's database. */
    public function run(): void
    {
        // Roles
        foreach (['admin', 'guru', 'bk', 'siswa'] as $peran) {
            Role::firstOrCreate(['name' => $peran, 'guard_name' => 'web']);
        }

        // Academic Year
        $academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026'],
            ['start_date' => now()->startOfYear(), 'is_active' => true]
        );

        // Users
        $admin = User::firstOrCreate(['email' => 'admin@admin.com'], ['name' => 'Admin', 'password' => Hash::make('password')]);
        $admin->syncRoles(['admin']);

        $guru1 = User::firstOrCreate(['email' => 'guru1@guru.com'], ['name' => 'Guru 1', 'password' => Hash::make('password')]);
        $guru2 = User::firstOrCreate(['email' => 'guru2@guru.com'], ['name' => 'Guru 2', 'password' => Hash::make('password')]);
        foreach ([$guru1, $guru2] as $user) { $user->syncRoles(['guru']); }

        $bk = User::firstOrCreate(['email' => 'bk@bk.com'], ['name' => 'BK', 'password' => Hash::make('password')]);
        $bk->syncRoles(['bk']);

        // Kelas
        $classA = SchoolClass::firstOrCreate(
            ['name' => 'X RPL 1', 'academic_year_id' => $academicYear->id],
            ['homeroom_teacher_id' => $guru1->id]
        );
        $classB = SchoolClass::firstOrCreate(
            ['name' => 'X RPL 2', 'academic_year_id' => $academicYear->id],
            ['homeroom_teacher_id' => $guru2->id]
        );

        // Siswa
        $siswa1User = User::firstOrCreate(['email' => 'siswa1@siswa.com'], ['name' => 'Siswa 1', 'password' => Hash::make('password')]);
        $siswa1User->syncRoles(['siswa']);
        Student::firstOrCreate(
            ['nis' => 'NIS-001'],
            ['user_id' => $siswa1User->id, 'class_id' => $classA->id, 'nisn' => '001', 'full_name' => 'Siswa 1', 'status' => 'active']
        );

        $siswa2User = User::firstOrCreate(['email' => 'siswa2@siswa.com'], ['name' => 'Siswa 2', 'password' => Hash::make('password')]);
        $siswa2User->syncRoles(['siswa']);
        Student::firstOrCreate(
            ['nis' => 'NIS-002'],
            ['user_id' => $siswa2User->id, 'class_id' => $classA->id, 'nisn' => '002', 'full_name' => 'Siswa 2', 'status' => 'active']
        );

        // Violation Categories
        ViolationCategory::firstOrCreate(
            ['code' => 'TEST_1500'],
            ['name' => 'Pelanggaran Test 1500', 'points' => 1500, 'status' => 'active']
        );
        ViolationCategory::firstOrCreate(
            ['code' => 'TEST_500'],
            ['name' => 'Pelanggaran Test 500', 'points' => 500, 'status' => 'active']
        );
    }
}
