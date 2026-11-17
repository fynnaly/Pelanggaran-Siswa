<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['admin', 'guru', 'wali_kelas', 'bk', 'siswa', 'kesiswaan', 'kepala_sekolah'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('shows the account profile photo next to the student name', function () {
    $year = AcademicYear::create([
        'name' => '2026/2027',
        'start_date' => now(),
        'is_active' => true,
        'status' => 'active',
    ]);

    $class = SchoolClass::create([
        'academic_year_id' => $year->id,
        'name' => 'VII 1',
        'grade_level' => 'VII',
        'status' => 'active',
    ]);

    $account = User::factory()->create(['avatar_path' => 'avatars/siswa-contoh.png']);
    $account->assignRole('siswa');

    $student = Student::create([
        'user_id' => $account->id,
        'nisn' => '0010000001',
        'nis' => '202412010002',
        'full_name' => 'Siswa Berfoto',
        'gender' => 'L',
        'status' => 'active',
    ]);

    Enrollment::create([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'academic_year_id' => $year->id,
        'status' => 'active',
        'enrolled_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('students.index'))
        ->assertOk()
        ->assertSee('Siswa Berfoto')
        ->assertSee('src="'.asset('storage/avatars/siswa-contoh.png').'"', false);
});
