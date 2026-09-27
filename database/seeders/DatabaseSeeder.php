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
        // Roles — 7 role sesuai ROMBAK 01-user.md + SOP jenjang
        foreach (['admin', 'guru', 'bk', 'siswa', 'kesiswaan', 'kepala_sekolah'] as $peran) {
            Role::firstOrCreate(['name' => $peran, 'guard_name' => 'web']);
        }

        // Academic Year
        $academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026'],
            [
                'start_date' => now()->startOfYear(),
                'end_date'   => now()->startOfYear()->addYear()->subDay(),
                'status'     => AcademicYear::STATUS_ACTIVE,
                'is_locked'  => false,
                'is_active'  => true,
            ]
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
            ['grade_level' => 'X', 'major' => 'RPL', 'homeroom_teacher_id' => $guru1->id]
        );
        $classB = SchoolClass::firstOrCreate(
            ['name' => 'X RPL 2', 'academic_year_id' => $academicYear->id],
            ['grade_level' => 'X', 'major' => 'RPL', 'homeroom_teacher_id' => $guru2->id]
        );

        // Siswa
        $siswa1User = User::firstOrCreate(['email' => 'siswa1@siswa.com'], ['name' => 'Siswa 1', 'password' => Hash::make('password')]);
        $siswa1User->syncRoles(['siswa']);
        $siswa1 = Student::firstOrCreate(
            ['nis' => 'NIS-001'],
            ['user_id' => $siswa1User->id, 'nisn' => '001', 'full_name' => 'Siswa 1', 'gender' => 'L', 'status' => 'active']
        );

        $siswa2User = User::firstOrCreate(['email' => 'siswa2@siswa.com'], ['name' => 'Siswa 2', 'password' => Hash::make('password')]);
        $siswa2User->syncRoles(['siswa']);
        $siswa2 = Student::firstOrCreate(
            ['nis' => 'NIS-002'],
            ['user_id' => $siswa2User->id, 'nisn' => '002', 'full_name' => 'Siswa 2', 'gender' => 'P', 'status' => 'active']
        );

        // Enrollment — siswa terdaftar di kelas melalui enrollment
        foreach ([[$siswa1, $classA], [$siswa2, $classA]] as [$siswa, $kelas]) {
            \App\Models\Enrollment::firstOrCreate(
                ['student_id' => $siswa->id, 'academic_year_id' => $academicYear->id],
                ['class_id' => $kelas->id, 'status' => 'active', 'enrolled_at' => now()]
            );
        }

        // Violation Categories
        // recurring_increment = tambahan poin per pengulangan (flat addition).
        // Cap max_recurring_points supaya pengulangan tak terbatas tidak drain saldo.
        ViolationCategory::firstOrCreate(
            ['code' => 'B.15'],
            [
                'name' => 'Terlambat < 10 menit',
                'severity' => 'ringan',
                'points' => 5,
                'group_type' => 'kehadiran',
                'is_recurring' => true,
                'recurring_increment' => 5,
                'max_recurring_points' => 25,
                'description' => 'Masuk kelas lebih dari 5 menit, kurang dari 10 menit. Ketiga angka (5/10/15) adalah poin untuk pelanggaran ke-1/ke-2/ke-3.',
                'status' => 'active',
            ]
        );
        ViolationCategory::firstOrCreate(
            ['code' => 'B.16'],
            [
                'name' => 'Terlambat > 10 menit',
                'severity' => 'ringan',
                'points' => 10,
                'group_type' => 'kehadiran',
                'is_recurring' => true,
                'recurring_increment' => 5,
                'max_recurring_points' => 30,
                'description' => 'Masuk kelas lebih dari 10 menit, kurang dari 30 menit. Ketiga angka (10/15/20) adalah poin untuk pelanggaran ke-1/ke-2/ke-3.',
                'status' => 'active',
            ]
        );
        ViolationCategory::firstOrCreate(
            ['code' => 'B.17'],
            [
                'name' => 'Terlambat > 30 menit',
                'severity' => 'sedang',
                'points' => 15,
                'group_type' => 'kehadiran',
                'is_recurring' => true,
                'recurring_increment' => 5,
                'max_recurring_points' => 40,
                'description' => 'Masuk kelas lebih dari 30 menit. Ketiga angka (15/20/25) adalah poin untuk pelanggaran ke-1/ke-2/ke-3.',
                'status' => 'active',
            ]
        );
        ViolationCategory::firstOrCreate(
            ['code' => 'A.08'],
            [
                'name' => 'Melawan guru',
                'severity' => 'berat',
                'points' => 100,
                'group_type' => 'sikap',
                'is_recurring' => true,
                'recurring_increment' => 25,
                'max_recurring_points' => 300,
                'description' => 'Perilaku membangkang terhadap guru di sekolah.',
                'status' => 'active',
            ]
        );
        ViolationCategory::firstOrCreate(
            ['code' => 'C.01'],
            [
                'name' => 'Bolos sekolah',
                'severity' => 'berat',
                'points' => 50,
                'group_type' => 'kehadiran',
                'is_recurring' => true,
                'recurring_increment' => 10,
                'max_recurring_points' => 200,
                'description' => 'Tidak hadir tanpa keterangan resmi.',
                'status' => 'active',
            ]
        );
        ViolationCategory::firstOrCreate(
            ['code' => 'D.02'],
            [
                'name' => 'Merusak barang sekolah',
                'severity' => 'berat',
                'points' => 200,
                'group_type' => 'perilaku',
                'is_zero_tolerance' => true,
                'is_recurring' => true,
                'recurring_increment' => 50,
                'max_recurring_points' => 500,
                'description' => 'Merusak fasilitas atau barang milik sekolah.',
                'status' => 'active',
            ]
        );
        ViolationCategory::firstOrCreate(
            ['code' => 'E.05'],
            [
                'name' => 'Pemakaian telepon genggam dan internet di kelas',
                'severity' => 'ringan',
                'points' => 5,
                'group_type' => 'sikap',
                'is_recurring' => true,
                'recurring_increment' => 2,
                'max_recurring_points' => 25,
                'description' => 'Menggunakan telepon genggam dan akses internet di dalam kelas tanpa izin.',
                'status' => 'active',
            ]
        );

        ViolationCategory::firstOrCreate(
            ['code' => 'TEST_1500'],
            ['name' => 'Pelanggaran Test 1500', 'severity' => 'berat', 'points' => 1500, 'group_type' => 'sikap', 'status' => 'active']
        );
        ViolationCategory::firstOrCreate(
            ['code' => 'TEST_500'],
            ['name' => 'Pelanggaran Test 500', 'severity' => 'sedang', 'points' => 500, 'group_type' => 'sikap', 'status' => 'active']
        );
    }
}
