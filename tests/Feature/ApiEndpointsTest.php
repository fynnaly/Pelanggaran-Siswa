<?php

use App\Models\AcademicYear;
use App\Models\DisciplineCase;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Models\ViolationCategory;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'bk', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

    $this->year = AcademicYear::create([
        'name' => '2026/2027',
        'start_date' => now(),
        'is_active' => true,
        'status' => 'active',
    ]);

    $this->class = SchoolClass::create([
        'name' => 'X RPL 1',
        'academic_year_id' => $this->year->id,
        'grade_level' => 'X',
        'status' => 'active',
    ]);

    $this->student = Student::create([
        'full_name' => 'Budi Santoso',
        'nis' => 'NIS-015',
        'nisn' => '0012345678',
        'nfc_uid' => '04A23B891F6080',
        'gender' => 'L',
        'status' => 'active',
    ]);

    $this->enrollment = Enrollment::create([
        'student_id' => $this->student->id,
        'class_id' => $this->class->id,
        'academic_year_id' => $this->year->id,
        'status' => 'active',
    ]);

    $this->parentCategory = ViolationCategory::create([
        'code' => 'P1',
        'name' => 'Kerapian & Atribut',
        'severity' => 'Ringan',
        'points' => 5,
        'status' => 'active',
    ]);

    $this->subCategory = ViolationCategory::create([
        'parent_id' => $this->parentCategory->id,
        'code' => 'P1.1',
        'name' => 'Tidak memakai dasi / ikat pinggang standar',
        'severity' => 'Ringan',
        'points' => 5,
        'is_recurring' => false,
        'status' => 'active',
    ]);

    $this->guruUser = User::factory()->create(['name' => 'Andi Pratama, S.Pd.']);
    $this->guruUser->assignRole('guru');

    $this->siswaUser = User::factory()->create(['name' => 'Budi Siswa']);
    $this->siswaUser->assignRole('siswa');
});

it('searches students for autocomplete', function () {
    Sanctum::actingAs($this->guruUser);

    $response = $this->getJson('/api/v1/students/search?q=Budi');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'nis', 'nisn', 'nama', 'kelas', 'saldo_poin', 'zona'],
            ],
        ])
        ->assertJsonPath('data.0.id', $this->student->id)
        ->assertJsonPath('data.0.nama', 'Budi Santoso')
        ->assertJsonPath('data.0.kelas', 'X RPL 1');
});

it('returns empty array when search query is shorter than 2 chars', function () {
    Sanctum::actingAs($this->guruUser);

    $response = $this->getJson('/api/v1/students/search?q=b');

    $response->assertOk()
        ->assertExactJson(['data' => []]);
});

it('forbids siswa role from searching students', function () {
    Sanctum::actingAs($this->siswaUser);

    $this->getJson('/api/v1/students/search?q=budi')
        ->assertForbidden();
});

it('fetches violation categories grouped and flat', function () {
    Sanctum::actingAs($this->siswaUser);

    // Grouped (default)
    $groupedRes = $this->getJson('/api/v1/violation-categories');
    $groupedRes->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'code', 'name',
                    'sub_categories' => [
                        '*' => ['id', 'code', 'name', 'points', 'is_recurring'],
                    ],
                ],
            ],
        ])
        ->assertJsonPath('data.0.code', 'P1')
        ->assertJsonPath('data.0.sub_categories.0.code', 'P1.1');

    // Flat
    $flatRes = $this->getJson('/api/v1/violation-categories?grouped=false');
    $flatRes->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'code', 'name', 'points', 'is_recurring'],
            ],
        ])
        ->assertJsonPath('data.0.code', 'P1.1');
});

it('scans student nametag by nfc uid', function () {
    Sanctum::actingAs($this->guruUser);

    $response = $this->getJson('/api/v1/students/by-nfc/04a23b891f6080');

    $response->assertOk()
        ->assertJsonStructure([
            'siswa' => ['id', 'nisn', 'nis', 'nama', 'kelas'],
            'saldo',
            'zona' => ['nama', 'penanggung_jawab', 'tindakan'],
        ])
        ->assertJsonPath('siswa.id', $this->student->id)
        ->assertJsonPath('siswa.nama', 'Budi Santoso');
});

it('returns 404 when scanning unregistered nfc uid', function () {
    Sanctum::actingAs($this->guruUser);

    $response = $this->getJson('/api/v1/students/by-nfc/UNKNOWN999');

    $response->assertNotFound()
        ->assertJson([
            'message' => 'Nametag belum terdaftar pada siswa mana pun.',
        ]);
});

it('creates new discipline case from field report', function () {
    Sanctum::actingAs($this->guruUser);

    $payload = [
        'student_id' => $this->student->id,
        'violation_category_id' => $this->subCategory->id,
        'incident_date' => '2026-11-12',
        'incident_time' => '08:15',
        'location' => 'Gerbang Depan Sekolah',
        'notes' => 'Tidak memakai dasi saat upacara bendera hari Senin.',
    ];

    $response = $this->postJson('/api/v1/discipline-cases', $payload);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Laporan pelanggaran berhasil dicatat (status: Temuan).',
            'data' => [
                'student_name' => 'Budi Santoso',
                'violation' => 'Tidak memakai dasi / ikat pinggang standar',
                'points' => 5,
                'status' => 'found',
                'reporter' => 'Andi Pratama, S.Pd.',
            ],
        ]);

    $this->assertDatabaseHas('discipline_cases', [
        'student_id' => $this->student->id,
        'violation_category_id' => $this->subCategory->id,
        'description' => 'Tidak memakai dasi saat upacara bendera hari Senin.',
        'status' => 'found',
        'reporter_id' => $this->guruUser->id,
    ]);
});

it('validates discipline case input and rejects parent categories', function () {
    Sanctum::actingAs($this->guruUser);

    // Missing notes
    $resMissingNotes = $this->postJson('/api/v1/discipline-cases', [
        'student_id' => $this->student->id,
        'violation_category_id' => $this->subCategory->id,
    ]);
    $resMissingNotes->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['notes']]);

    // Parent category chosen instead of sub category
    $resParentCat = $this->postJson('/api/v1/discipline-cases', [
        'student_id' => $this->student->id,
        'violation_category_id' => $this->parentCategory->id,
        'notes' => 'Melanggar aturan.',
    ]);
    $resParentCat->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['violation_category_id']]);
});

it('lists reported discipline cases with pagination', function () {
    Sanctum::actingAs($this->guruUser);

    DisciplineCase::create([
        'case_number' => 'KAS-2026-0042',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->subCategory->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $this->guruUser->id,
        'description' => 'Tidak memakai dasi.',
        'status' => 'found',
        'incident_date' => '2026-11-12',
    ]);

    $response = $this->getJson('/api/v1/discipline-cases');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'case_number', 'student_name', 'class_name',
                    'violation_name', 'points', 'status', 'incident_date', 'created_at',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'total'],
        ])
        ->assertJsonPath('data.0.student_name', 'Budi Santoso')
        ->assertJsonPath('data.0.case_number', 'KAS-2026-0042')
        ->assertJsonPath('meta.total', 1);
});
