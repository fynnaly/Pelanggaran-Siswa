<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\Academic\PromotionService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['admin', 'guru', 'wali_kelas', 'bk', 'siswa', 'kesiswaan', 'kepala_sekolah'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->year = AcademicYear::create([
        'name' => '2026/2027',
        'start_date' => now()->startOfYear(),
        'is_active' => true,
        'status' => 'active',
    ]);

    $this->withoutMiddleware(PreventRequestForgery::class);
});

it('creates a class with a numeric rombel', function () {
    $this->actingAs($this->admin)
        ->post(route('classes.store'), [
            'academic_year_id' => $this->year->id,
            'grade_level' => 'VII',
            'rombel' => '1',
        ])
        ->assertRedirect(route('classes.index'));

    $this->assertDatabaseHas('classes', [
        'academic_year_id' => $this->year->id,
        'name' => 'VII 1',
        'grade_level' => 'VII',
    ]);
});

it('creates a class with a letter rombel', function () {
    $this->actingAs($this->admin)
        ->post(route('classes.store'), [
            'academic_year_id' => $this->year->id,
            'grade_level' => 'VIII',
            'rombel' => 'a',
        ])
        ->assertRedirect(route('classes.index'));

    $this->assertDatabaseHas('classes', [
        'name' => 'VIII A',
        'grade_level' => 'VIII',
    ]);
});

it('rejects a duplicate class name in the same academic year', function () {
    SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'name' => 'VII 1',
        'grade_level' => 'VII',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->post(route('classes.store'), [
            'academic_year_id' => $this->year->id,
            'grade_level' => 'VII',
            'rombel' => '1',
        ])
        ->assertSessionHasErrors('rombel');
});

it('renders the edit form with the stored rombel', function () {
    $class = SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'name' => 'IX B',
        'grade_level' => 'IX',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->get(route('classes.edit', $class))
        ->assertOk()
        ->assertSee('IX B')
        ->assertSee('value="B"', false);
});

it('promotes VII to VIII and VIII to IX, then graduates IX', function () {
    $newYear = AcademicYear::create([
        'name' => '2027/2028',
        'start_date' => now()->addYear()->startOfYear(),
        'is_active' => false,
        'status' => 'active',
    ]);

    $classes = [];

    foreach ([['VII 1', 'VII'], ['VIII 1', 'VIII'], ['IX 1', 'IX']] as [$name, $grade]) {
        $classes[$grade] = SchoolClass::create([
            'academic_year_id' => $this->year->id,
            'name' => $name,
            'grade_level' => $grade,
            'status' => 'active',
        ]);
    }

    $students = [];

    foreach (['VII', 'VIII', 'IX'] as $index => $grade) {
        $student = Student::create([
            'full_name' => "Siswa {$grade}",
            'nis' => 'NIS-'.$index,
            'nisn' => '000000000'.$index,
            'gender' => 'L',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $classes[$grade]->id,
            'academic_year_id' => $this->year->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $students[$grade] = $student;
    }

    $result = app(PromotionService::class)->promote($newYear);

    expect($result['classes_created'])->toBe(2);

    $this->assertDatabaseHas('classes', [
        'academic_year_id' => $newYear->id,
        'name' => 'VIII 1',
        'grade_level' => 'VIII',
    ]);
    $this->assertDatabaseHas('classes', [
        'academic_year_id' => $newYear->id,
        'name' => 'IX 1',
        'grade_level' => 'IX',
    ]);

    expect($students['IX']->fresh()->status)->toBe('graduated');
    expect($students['VII']->fresh()->status)->toBe('active');
});
