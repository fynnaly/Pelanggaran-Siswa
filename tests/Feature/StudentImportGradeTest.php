<?php

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

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

function importStudents($test, string $csv): void
{
    $test->actingAs($test->admin)
        ->from(route('students.index'))
        ->post(route('students.import'), [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])
        ->assertSessionHas('success');
}

it('sets the grade level when the class name already contains the grade', function () {
    importStudents($this, implode("\n", [
        'NIS,Nama Lengkap,Kelas (nama)',
        '2425.8.001,Siti Aminah,VIII B',
    ]));

    $class = SchoolClass::where('name', 'VIII B')->firstOrFail();

    expect($class->grade_level)->toBe('VIII')
        ->and(Student::where('nis', '2425.8.001')->value('full_name'))->toBe('Siti Aminah');
});

it('composes the class name from the Tingkatan column', function () {
    importStudents($this, implode("\n", [
        'NIS,Nama Lengkap,Kelas (nama),Tingkatan',
        '2425.9.001,Ani Lestari,A,IX',
    ]));

    $class = SchoolClass::where('name', 'IX A')->firstOrFail();

    expect($class->grade_level)->toBe('IX')
        ->and(Student::where('nis', '2425.9.001')->firstOrFail()->currentEnrollment->class_id)->toBe($class->id);
});

it('normalizes numeric class names to roman grade and letter rombel', function () {
    importStudents($this, implode("\n", [
        'NIS,Nama Lengkap,Kelas (nama)',
        '2425.7.001,Aditya Pratama,7.1',
    ]));

    expect(SchoolClass::where('name', 'VII A')->where('grade_level', 'VII')->exists())->toBeTrue();
});

it('enrolls into an existing class matching the grade when class name is empty', function () {
    $seven = SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'name' => 'VII A',
        'grade_level' => 'VII',
        'status' => 'active',
    ]);
    $nine = SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'name' => 'IX A',
        'grade_level' => 'IX',
        'status' => 'active',
    ]);

    importStudents($this, implode("\n", [
        'NIS,Nama Lengkap,Tingkatan',
        '2425.9.002,Bunga Lestari,IX',
    ]));

    $enrollment = Student::where('nis', '2425.9.002')->firstOrFail()->currentEnrollment;

    expect($enrollment->class_id)->toBe($nine->id)
        ->and($enrollment->class_id)->not->toBe($seven->id);
});

it('skips a row when the requested grade has no class', function () {
    SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'name' => 'VII A',
        'grade_level' => 'VII',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->from(route('students.index'))
        ->post(route('students.import'), [
            'file' => UploadedFile::fake()->createWithContent('students.csv', implode("\n", [
                'NIS,Nama Lengkap,Tingkatan',
                '2425.9.003,Citra Dewi,IX',
            ])),
        ])
        ->assertSessionHas('success', fn (string $message) => str_contains($message, '0 siswa ditambahkan')
            && str_contains($message, 'belum ada kelas untuk tingkatan IX'));

    expect(Student::where('nis', '2425.9.003')->exists())->toBeFalse();
});
