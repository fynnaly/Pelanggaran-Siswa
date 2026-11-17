<?php

use App\Models\AcademicYear;
use App\Models\DisciplineCase;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Models\ViolationCategory;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['admin', 'guru', 'wali_kelas', 'siswa'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->year = AcademicYear::create([
        'name' => '2026/2027',
        'start_date' => '2026-07-01',
        'is_active' => true,
        'status' => 'active',
    ]);

    $this->classA = SchoolClass::create([
        'name' => 'VII A',
        'academic_year_id' => $this->year->id,
        'grade_level' => 'VII',
        'status' => 'active',
    ]);
    $this->classB = SchoolClass::create([
        'name' => 'IX B',
        'academic_year_id' => $this->year->id,
        'grade_level' => 'IX',
        'status' => 'active',
    ]);

    $this->violation = ViolationCategory::create([
        'code' => 'V-SORT',
        'name' => 'Terlambat',
        'severity' => 'ringan',
        'points' => 5,
        'status' => 'active',
    ]);
});

function studentWithClass(string $name, string $nis, SchoolClass $class, AcademicYear $year): Student
{
    $student = Student::create([
        'full_name' => $name,
        'nis' => $nis,
        'nisn' => 'NISN-'.$nis,
        'gender' => 'L',
        'status' => 'active',
    ]);

    Enrollment::create([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'academic_year_id' => $year->id,
        'status' => 'active',
    ]);

    return $student;
}

it('sorts the class report by student count and case count', function () {
    studentWithClass('Siswa Satu', 'NIS-SORT-1', $this->classA, $this->year);
    studentWithClass('Siswa Dua', 'NIS-SORT-2', $this->classA, $this->year);
    studentWithClass('Siswa Tiga', 'NIS-SORT-3', $this->classB, $this->year);

    DisciplineCase::create([
        'case_number' => 'KAS-SORT-1',
        'student_id' => Student::where('nis', 'NIS-SORT-1')->value('id'),
        'class_id' => $this->classB->id,
        'violation_category_id' => $this->violation->id,
        'academic_year_id' => $this->year->id,
        'description' => 'Kasus sortir.',
        'status' => 'handling',
        'incident_date' => '2026-11-10',
    ]);

    $this->actingAs($this->admin)
        ->get(route('laporan.kelas.index', ['year' => '', 'sort' => 'students', 'order' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder(['VII A', 'IX B']);

    $this->actingAs($this->admin)
        ->get(route('laporan.kelas.index', ['year' => '', 'sort' => 'students', 'order' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder(['IX B', 'VII A']);

    $this->actingAs($this->admin)
        ->get(route('laporan.kelas.index', ['year' => '', 'sort' => 'kasus', 'order' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder(['IX B', 'VII A']);
});

it('sorts the student report by name, nis, class, and violation count', function () {
    studentWithClass('Budi Sortir', 'NIS-SORT-20', $this->classB, $this->year);
    studentWithClass('Andi Sortir', 'NIS-SORT-10', $this->classA, $this->year);

    DisciplineCase::create([
        'case_number' => 'KAS-SORT-2',
        'student_id' => Student::where('nis', 'NIS-SORT-20')->value('id'),
        'class_id' => $this->classB->id,
        'violation_category_id' => $this->violation->id,
        'academic_year_id' => $this->year->id,
        'description' => 'Kasus sortir siswa.',
        'status' => 'handling',
        'incident_date' => '2026-11-10',
    ]);

    $this->actingAs($this->admin)
        ->get(route('laporan.siswa.index', ['sort' => 'name', 'order' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder(['Andi Sortir', 'Budi Sortir']);

    $this->actingAs($this->admin)
        ->get(route('laporan.siswa.index', ['sort' => 'nis', 'order' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder(['NIS-SORT-20', 'NIS-SORT-10']);

    $this->actingAs($this->admin)
        ->get(route('laporan.siswa.index', ['sort' => 'class', 'order' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder(['Budi Sortir', 'Andi Sortir']);

    $this->actingAs($this->admin)
        ->get(route('laporan.siswa.index', ['sort' => 'kasus', 'order' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder(['Budi Sortir', 'Andi Sortir']);
});

it('ignores unsupported report sort columns', function () {
    studentWithClass('Andi Sortir', 'NIS-SORT-10', $this->classA, $this->year);
    studentWithClass('Budi Sortir', 'NIS-SORT-20', $this->classB, $this->year);

    $this->actingAs($this->admin)
        ->get(route('laporan.siswa.index', ['sort' => 'password', 'order' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder(['Andi Sortir', 'Budi Sortir']);

    $this->actingAs($this->admin)
        ->get(route('laporan.kelas.index', ['year' => '', 'sort' => 'password', 'order' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder(['IX B', 'VII A']);
});
