<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Support\Xlsx;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

    $this->year = AcademicYear::create([
        'name' => '2026/2027',
        'start_date' => now(),
        'is_active' => true,
        'status' => 'active',
    ]);

    $this->class = SchoolClass::create([
        'name' => 'VII 1',
        'academic_year_id' => $this->year->id,
        'grade_level' => 'VII',
        'status' => 'active',
    ]);

    $this->student1 = Student::create([
        'full_name' => 'Ahmad Rizki',
        'nis' => 'NIS-001',
        'nisn' => '0011111111',
        'nfc_uid' => '04A23B891F6080',
        'gender' => 'L',
        'status' => 'active',
    ]);

    $this->student2 = Student::create([
        'full_name' => 'Budi Santoso',
        'nis' => 'NIS-002',
        'nisn' => '0022222222',
        'gender' => 'L',
        'status' => 'active',
    ]);

    Enrollment::create([
        'student_id' => $this->student1->id,
        'class_id' => $this->class->id,
        'academic_year_id' => $this->year->id,
        'status' => 'active',
    ]);

    Enrollment::create([
        'student_id' => $this->student2->id,
        'class_id' => $this->class->id,
        'academic_year_id' => $this->year->id,
        'status' => 'active',
    ]);

    $this->adminUser = User::factory()->create(['name' => 'Admin Test']);
    $this->adminUser->assignRole('admin');

    $this->siswaUser = User::factory()->create(['name' => 'Siswa Test']);
    $this->siswaUser->assignRole('siswa');
});

it('renders nametag nfc index for staff and displays students with link', function () {
    $this->actingAs($this->adminUser)
        ->get(route('nametag-nfc.index'))
        ->assertOk()
        ->assertSee('Data Nametag NFC Siswa')
        ->assertSee('04A23B891F6080')
        ->assertSee('Ahmad Rizki')
        ->assertSee(route('students.show', $this->student1))
        ->assertSee('Budi Santoso');
});

it('forbids siswa role from accessing nametag nfc index', function () {
    $this->actingAs($this->siswaUser)
        ->get(route('nametag-nfc.index'))
        ->assertForbidden();
});

it('pairs nfc uid to a student', function () {
    $this->actingAs($this->adminUser)
        ->post(route('nametag-nfc.store'), [
            'student_id' => $this->student2->id,
            'nfc_uid' => '04B33C992F7090',
        ])
        ->assertRedirect(route('nametag-nfc.index'))
        ->assertSessionHas('success');

    expect($this->student2->fresh()->nfc_uid)->toBe('04B33C992F7090');
});

it('rejects duplicate nfc uid on store and update', function () {
    $this->actingAs($this->adminUser)
        ->post(route('nametag-nfc.store'), [
            'student_id' => $this->student2->id,
            'nfc_uid' => '04A23B891F6080', // already student1's
        ])
        ->assertSessionHasErrors(['nfc_uid']);

    $this->actingAs($this->adminUser)
        ->put(route('nametag-nfc.update', $this->student2), [
            'nfc_uid' => '04A23B891F6080',
        ])
        ->assertSessionHasErrors(['nfc_uid']);
});

it('updates and unpairs nfc uid from student', function () {
    $this->actingAs($this->adminUser)
        ->put(route('nametag-nfc.update', $this->student1), [
            'nfc_uid' => 'NEW-UID-1234',
        ])
        ->assertRedirect(route('nametag-nfc.index'));

    expect($this->student1->fresh()->nfc_uid)->toBe('NEW-UID-1234');

    // Unpair via destroy
    $this->actingAs($this->adminUser)
        ->delete(route('nametag-nfc.destroy', $this->student1))
        ->assertRedirect(route('nametag-nfc.index'));

    expect($this->student1->fresh()->nfc_uid)->toBeNull();
});

it('downloads template and exports nametag nfc data', function () {
    $templateRes = $this->actingAs($this->adminUser)
        ->get(route('nametag-nfc.template'));
    $templateRes->assertOk();
    expect($templateRes->headers->get('content-disposition'))->toContain('template-import-nametag-nfc.xlsx');

    $exportRes = $this->actingAs($this->adminUser)
        ->get(route('nametag-nfc.export'));
    $exportRes->assertOk();
    expect($exportRes->headers->get('content-disposition'))->toContain('.xlsx');
});

it('imports and matches nfc uids from xlsx file', function () {
    $rows = [
        ['NIS', 'Nama Siswa', 'UID Nametag NFC'],
        ['NIS-002', 'Budi Santoso', '04A99C881F9999'],
    ];

    $filePath = Xlsx::write($rows, 'import-test');
    $file = new UploadedFile($filePath, 'import-nfc.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $this->actingAs($this->adminUser)
        ->post(route('nametag-nfc.import'), ['file' => $file])
        ->assertRedirect(route('nametag-nfc.index'))
        ->assertSessionHas('success');

    expect($this->student2->fresh()->nfc_uid)->toBe('04A99C881F9999');

    if (file_exists($filePath)) {
        unlink($filePath);
    }
});
