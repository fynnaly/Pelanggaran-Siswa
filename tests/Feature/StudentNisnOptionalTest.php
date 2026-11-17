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

    $this->class = SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'name' => 'VII A',
        'grade_level' => 'VII',
        'status' => 'active',
    ]);

    $this->withoutMiddleware(PreventRequestForgery::class);
});

it('creates a student without a nisn', function () {
    $this->actingAs($this->admin)
        ->post(route('students.store'), [
            'nis' => '202412010001',
            'full_name' => 'Siswa Tanpa NISN',
            'gender' => 'L',
            'class_id' => $this->class->id,
            'academic_year_id' => $this->year->id,
        ])
        ->assertRedirect(route('students.index'));

    $student = Student::where('nis', '202412010001')->firstOrFail();

    expect($student->full_name)->toBe('Siswa Tanpa NISN')
        ->and($student->nisn)->toBeNull();
});

it('allows more than one student without a nisn', function () {
    foreach ([
        ['202412010002', 'Siswa Pertama'],
        ['202412010003', 'Siswa Kedua'],
    ] as [$nis, $name]) {
        $this->actingAs($this->admin)
            ->post(route('students.store'), [
                'nis' => $nis,
                'full_name' => $name,
                'gender' => 'P',
                'class_id' => $this->class->id,
                'academic_year_id' => $this->year->id,
            ])
            ->assertSessionHasNoErrors();
    }

    expect(Student::whereNull('nisn')->count())->toBe(2);
});

it('rejects a nisn that is not ten digits when filled', function () {
    $this->actingAs($this->admin)
        ->post(route('students.store'), [
            'nisn' => '12345',
            'nis' => '202412010004',
            'full_name' => 'Siswa NISN Salah',
            'gender' => 'L',
            'class_id' => $this->class->id,
            'academic_year_id' => $this->year->id,
        ])
        ->assertSessionHasErrors('nisn');

    expect(Student::where('nis', '202412010004')->exists())->toBeFalse();
});

it('imports students from a csv without a nisn column', function () {
    $csv = implode("\n", [
        'NIS,Nama Lengkap,Jenis Kelamin',
        '202412010005,Budi Import,Laki-laki',
        '202412010006,Siti Import,Perempuan',
    ]);

    $this->actingAs($this->admin)
        ->from(route('students.index'))
        ->post(route('students.import'), [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])
        ->assertRedirect(route('students.index'))
        ->assertSessionHas('success', fn (string $message) => str_contains($message, '2 siswa ditambahkan'));

    $budi = Student::where('nis', '202412010005')->firstOrFail();
    $siti = Student::where('nis', '202412010006')->firstOrFail();

    expect($budi->nisn)->toBeNull()
        ->and($siti->nisn)->toBeNull()
        ->and($budi->full_name)->toBe('Budi Import')
        ->and($siti->gender)->toBe('P');
});

it('imports students with an empty nisn value and still validates a filled nisn', function () {
    $csv = implode("\n", [
        'NISN,NIS,Nama Lengkap',
        ',202412010007,Ani Tanpa NISN',
        '123,202412010008,Budi NISN Salah',
    ]);

    $this->actingAs($this->admin)
        ->from(route('students.index'))
        ->post(route('students.import'), [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])
        ->assertRedirect(route('students.index'))
        ->assertSessionHas('success', fn (string $message) => str_contains($message, '1 siswa ditambahkan')
            && str_contains($message, '1 dilewati'));

    expect(Student::where('nis', '202412010007')->value('nisn'))->toBeNull()
        ->and(Student::where('nis', '202412010008')->exists())->toBeFalse();
});

it('accepts a dotted school nis when creating a student', function () {
    $this->actingAs($this->admin)
        ->post(route('students.store'), [
            'nis' => '2425.7.001',
            'full_name' => 'Aditya Pratama Suryanto',
            'gender' => 'L',
            'class_id' => $this->class->id,
            'academic_year_id' => $this->year->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('students.index'));

    expect(Student::where('nis', '2425.7.001')->exists())->toBeTrue();
});

it('imports students with a dotted school nis', function () {
    $csv = implode("\n", [
        'NIS,Nama Lengkap,Jenis Kelamin',
        '2425.7.001,ADITYA PRATAMA SURYANTO,Laki-laki',
        '2425.7.002,AINIYA FAIDA AZMI,Perempuan',
    ]);

    $this->actingAs($this->admin)
        ->from(route('students.index'))
        ->post(route('students.import'), [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])
        ->assertRedirect(route('students.index'))
        ->assertSessionHas('success', fn (string $message) => str_contains($message, '2 siswa ditambahkan')
            && ! str_contains($message, 'dilewati'));

    expect(Student::where('nis', '2425.7.001')->value('full_name'))->toBe('ADITYA PRATAMA SURYANTO')
        ->and(Student::where('nis', '2425.7.002')->value('gender'))->toBe('P');
});

it('rejects a school nis with unsupported characters', function () {
    $this->actingAs($this->admin)
        ->post(route('students.store'), [
            'nis' => '2425 7 001',
            'full_name' => 'Siswa NIS Salah',
            'gender' => 'L',
            'class_id' => $this->class->id,
            'academic_year_id' => $this->year->id,
        ])
        ->assertSessionHasErrors('nis');
});
