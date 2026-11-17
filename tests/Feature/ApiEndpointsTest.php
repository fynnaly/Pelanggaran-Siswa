<?php

use App\Models\AcademicYear;
use App\Models\AchievementCategory;
use App\Models\AchievementRecord;
use App\Models\DisciplineCase;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Models\ViolationCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'bk', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);

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

it('returns 422 when an inactive account attempts api login', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('searches students for autocomplete', function () {
    $this->class->update(['homeroom_teacher_id' => $this->guruUser->id]);
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
        ->assertJsonPath('data.0.kelas', 'VII 1');
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
    $this->class->update(['homeroom_teacher_id' => $this->guruUser->id]);
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

it('restricts student ledger and point monitoring to the authenticated student', function () {
    $this->student->update(['user_id' => $this->siswaUser->id]);
    $otherStudent = Student::create([
        'full_name' => 'Siswa Lain',
        'nis' => 'NIS-016',
        'nisn' => '0012345679',
        'gender' => 'L',
        'status' => 'active',
    ]);
    Sanctum::actingAs($this->siswaUser);

    $this->getJson("/api/v1/students/{$this->student->id}/points")->assertOk();
    $this->getJson("/api/v1/students/{$otherStudent->id}/points")->assertForbidden();

    $this->getJson('/api/v1/monitor-poin')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $this->student->id);
});

it('restricts the nfc lookup endpoint to school staff', function () {
    Sanctum::actingAs($this->siswaUser);

    $this->getJson('/api/v1/students/by-nfc/04A23B891F6080')->assertForbidden();
});

it('shows students only the cases they reported', function () {
    $otherReporter = User::factory()->create();
    $otherReporter->assignRole('guru');
    DisciplineCase::create([
        'case_number' => 'KAS-2026-0046',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->subCategory->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $this->guruUser->id,
        'description' => 'Kasus milik pelapor.',
        'status' => 'found',
    ]);
    DisciplineCase::create([
        'case_number' => 'KAS-2026-0047',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->subCategory->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $otherReporter->id,
        'description' => 'Kasus milik pelapor lain.',
        'status' => 'found',
    ]);
    Sanctum::actingAs($this->guruUser);

    $this->getJson('/api/v1/discipline-cases')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.case_number', 'KAS-2026-0046');
});

it('creates new discipline case from field report', function () {
    $this->class->update(['homeroom_teacher_id' => $this->guruUser->id]);
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

it('prevents a class teacher from reporting a case for a student outside their class', function () {
    $otherClass = SchoolClass::create([
        'name' => 'VII 2',
        'academic_year_id' => $this->year->id,
        'grade_level' => 'VII',
        'homeroom_teacher_id' => $this->guruUser->id,
        'status' => 'active',
    ]);
    Sanctum::actingAs($this->guruUser);

    $this->postJson('/api/v1/discipline-cases', [
        'student_id' => $this->student->id,
        'violation_category_id' => $this->subCategory->id,
        'notes' => 'Kasus di luar kelas.',
    ])->assertForbidden();

    $this->assertDatabaseMissing('discipline_cases', [
        'student_id' => $this->student->id,
        'description' => 'Kasus di luar kelas.',
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

it('blocks a student from changing their avatar through the api', function () {
    Storage::fake('public');
    $this->student->update(['user_id' => $this->siswaUser->id]);
    Sanctum::actingAs($this->siswaUser);

    $avatar = UploadedFile::fake()->createWithContent(
        'avatar.png',
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
    );

    $this->patchJson('/api/v1/profile', ['avatar' => $avatar])
        ->assertForbidden()
        ->assertJsonPath('message', 'Akun siswa bersifat hanya-baca. Hubungi petugas sekolah untuk perubahan.');

    $this->patchJson('/api/v1/profile', ['remove_avatar' => true])
        ->assertForbidden();

    $this->assertDatabaseHas('users', [
        'id' => $this->siswaUser->id,
        'avatar_path' => null,
    ]);
});

it('requires authentication to access the mobile profile endpoint', function () {
    $this->getJson('/api/v1/profile')->assertUnauthorized();
});

it('rejects an already authenticated api session for a deactivated account', function () {
    $user = User::factory()->create(['is_active' => false]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/profile')
        ->assertForbidden()
        ->assertJsonPath('message', 'Akun dinonaktifkan. Hubungi pengelola sekolah.');
});

it('documents the new mobile profile, achievement, and report endpoints', function () {
    $this->getJson('/api/v1')
        ->assertOk()
        ->assertJsonFragment(['GET profile (auth:sanctum)'])
        ->assertJsonFragment(['POST achievement-records (auth:sanctum)'])
        ->assertJsonFragment(['GET reports/classes/{schoolClass} (auth:sanctum)']);
});

it('blocks students from changing protected profile fields through the api', function () {
    $this->student->update(['user_id' => $this->siswaUser->id]);
    Sanctum::actingAs($this->siswaUser);

    $this->patchJson('/api/v1/profile', [
        'name' => 'Nama Diubah',
        'email' => 'diubah@example.test',
        'nip' => 'NIP-123',
    ])->assertForbidden();

    $this->assertDatabaseHas('users', [
        'id' => $this->siswaUser->id,
        'name' => 'Budi Siswa',
        'email' => $this->siswaUser->email,
    ]);
});

it('blocks students from changing their password through the api', function () {
    Sanctum::actingAs($this->siswaUser);

    $this->patchJson('/api/v1/auth/password', [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertForbidden()
        ->assertJsonPath('message', 'Akun siswa bersifat hanya-baca. Hubungi petugas sekolah untuk perubahan.');
});

it('lets authenticated users list active achievement subcategories', function () {
    $parent = AchievementCategory::create([
        'code' => 'R4',
        'name' => 'Akademik',
        'points' => 0,
        'status' => 'active',
    ]);
    $activeCategory = AchievementCategory::create([
        'parent_id' => $parent->id,
        'code' => 'R4.1',
        'name' => 'Juara lomba',
        'points' => 15,
        'status' => 'active',
    ]);
    AchievementCategory::create([
        'parent_id' => $parent->id,
        'code' => 'R4.2',
        'name' => 'Kategori draft',
        'points' => 20,
        'status' => 'draft',
    ]);
    Sanctum::actingAs($this->siswaUser);

    $this->getJson('/api/v1/achievement-categories')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $activeCategory->id)
        ->assertJsonPath('data.0.parent_name', 'Akademik');
});

it('prevents students from creating achievement records through the api', function () {
    Sanctum::actingAs($this->siswaUser);

    $this->postJson('/api/v1/achievement-records', [])
        ->assertForbidden();

    $this->assertDatabaseCount('achievement_records', 0);
});

it('allows students to read only their own achievement records', function () {
    $this->student->update(['user_id' => $this->siswaUser->id]);
    $categoryParent = AchievementCategory::create([
        'code' => 'R1',
        'name' => 'Akademik',
        'points' => 0,
        'status' => 'active',
    ]);
    $category = AchievementCategory::create([
        'parent_id' => $categoryParent->id,
        'code' => 'R1.1',
        'name' => 'Juara kelas',
        'points' => 10,
        'status' => 'active',
    ]);
    AchievementRecord::create([
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'achievement_category_id' => $category->id,
        'academic_year_id' => $this->year->id,
        'recorded_by' => $this->guruUser->id,
        'status' => 'pending',
        'event_name' => 'Olimpiade Matematika',
        'points_awarded' => 10,
    ]);
    Sanctum::actingAs($this->siswaUser);

    $this->getJson("/api/v1/students/{$this->student->id}/achievements")
        ->assertOk()
        ->assertJsonPath('data.0.event_name', 'Olimpiade Matematika')
        ->assertJsonPath('data.0.status', 'pending');

    $otherStudent = Student::create([
        'full_name' => 'Siswa Lain',
        'nis' => 'NIS-016',
        'nisn' => '0012345679',
        'gender' => 'L',
        'status' => 'active',
    ]);

    $this->getJson("/api/v1/students/{$otherStudent->id}/achievements")
        ->assertForbidden();
});

it('records a linked achievement through the api and verifies it for bk staff', function () {
    $categoryParent = AchievementCategory::create([
        'code' => 'R2',
        'name' => $this->parentCategory->name,
        'points' => 0,
        'status' => 'active',
    ]);
    $category = AchievementCategory::create([
        'parent_id' => $categoryParent->id,
        'code' => 'R2.1',
        'name' => 'Kerapian Teladan',
        'points' => 12,
        'status' => 'active',
    ]);
    $case = DisciplineCase::create([
        'case_number' => 'KAS-2026-0043',
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
    $bkUser = User::factory()->create();
    $bkUser->assignRole('bk');
    Sanctum::actingAs($bkUser);

    $response = $this->postJson('/api/v1/achievement-records', [
        'student_id' => $this->student->id,
        'achievement_category_id' => $category->id,
        'discipline_case_id' => $case->id,
        'event_name' => 'Program Kerapian',
        'event_date' => '2026-11-15',
        'description' => 'Mengikuti program pembinaan.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'verified')
        ->assertJsonPath('data.points_awarded', 12)
        ->assertJsonPath('data.discipline_case.id', $case->id);

    $record = AchievementRecord::firstOrFail();
    $this->assertDatabaseHas('achievement_records', [
        'id' => $record->id,
        'student_id' => $this->student->id,
        'class_id' => $this->class->id,
        'discipline_case_id' => $case->id,
        'status' => 'verified',
        'points_awarded' => 12,
    ]);
    $this->assertDatabaseHas('point_ledgers', [
        'student_id' => $this->student->id,
        'source_type' => AchievementRecord::class,
        'source_id' => $record->id,
        'direction' => 'credit',
        'amount' => 12,
    ]);
});

it('rejects an achievement category that does not match the linked case', function () {
    $categoryParent = AchievementCategory::create([
        'code' => 'R3',
        'name' => 'Prestasi Berbeda',
        'points' => 0,
        'status' => 'active',
    ]);
    $category = AchievementCategory::create([
        'parent_id' => $categoryParent->id,
        'code' => 'R3.1',
        'name' => 'Kategori Tidak Cocok',
        'points' => 8,
        'status' => 'active',
    ]);
    $case = DisciplineCase::create([
        'case_number' => 'KAS-2026-0044',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->subCategory->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $this->guruUser->id,
        'description' => 'Tidak memakai dasi.',
        'status' => 'found',
    ]);
    $this->class->update(['homeroom_teacher_id' => $this->guruUser->id]);
    Sanctum::actingAs($this->guruUser);

    $this->postJson('/api/v1/achievement-records', [
        'student_id' => $this->student->id,
        'achievement_category_id' => $category->id,
        'discipline_case_id' => $case->id,
        'event_name' => 'Program pembinaan',
    ])->assertUnprocessable()
        ->assertInvalid(['achievement_category_id']);

    $this->assertDatabaseMissing('achievement_records', [
        'discipline_case_id' => $case->id,
    ]);
});

it('returns a student report and restricts class reports to a wali kelas scope', function () {
    $this->student->update(['user_id' => $this->siswaUser->id]);
    $case = DisciplineCase::create([
        'case_number' => 'KAS-2026-0045',
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
    Sanctum::actingAs($this->siswaUser);

    $this->getJson("/api/v1/reports/students/{$this->student->id}?year=")
        ->assertOk()
        ->assertJsonPath('data.students.0.student.id', $this->student->id)
        ->assertJsonPath('data.students.0.cases.0.case_number', $case->case_number);

    $wali = User::factory()->create();
    $wali->assignRole('wali_kelas');
    $this->class->update(['homeroom_teacher_id' => $wali->id]);
    $otherClass = SchoolClass::create([
        'name' => 'VII 2',
        'academic_year_id' => $this->year->id,
        'grade_level' => 'VII',
        'status' => 'active',
    ]);
    Sanctum::actingAs($wali);

    $this->getJson("/api/v1/reports/classes/{$this->class->id}")
        ->assertOk()
        ->assertJsonPath('data.students.0.student.id', $this->student->id);

    $this->getJson("/api/v1/reports/classes/{$otherClass->id}")
        ->assertForbidden();
});
