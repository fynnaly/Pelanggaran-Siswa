<?php

use App\Models\AcademicYear;
use App\Models\AchievementCategory;
use App\Models\AchievementRecord;
use App\Models\DisciplineCase;
use App\Models\Enrollment;
use App\Models\PointLedger;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Models\ViolationCategory;
use App\Support\Xlsx;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['admin', 'guru', 'wali_kelas', 'siswa'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create(['name' => 'Admin Laporan']);
    $this->admin->assignRole('admin');
    $this->wali = User::factory()->create(['name' => 'Wali Kelas']);
    $this->wali->assignRole('wali_kelas');

    $this->activeYear = AcademicYear::create([
        'name' => '2026/2027',
        'start_date' => '2026-07-01',
        'is_active' => true,
        'status' => 'active',
    ]);
    $this->oldYear = AcademicYear::create([
        'name' => '2025/2026',
        'start_date' => '2025-07-01',
        'is_active' => false,
        'status' => 'active',
    ]);

    $this->classA = SchoolClass::create([
        'name' => 'VII A',
        'academic_year_id' => $this->activeYear->id,
        'grade_level' => 'VII',
        'homeroom_teacher_id' => $this->wali->id,
        'status' => 'active',
    ]);
    $this->classB = SchoolClass::create([
        'name' => 'IX A',
        'academic_year_id' => $this->oldYear->id,
        'grade_level' => 'IX',
        'status' => 'active',
    ]);

    $this->student = Student::create([
        'full_name' => 'Siswa Uji Laporan',
        'nis' => 'NIS-LAP-1',
        'nisn' => '1234567890',
        'gender' => 'L',
        'status' => 'active',
    ]);
    $this->activeEnrollment = Enrollment::create([
        'student_id' => $this->student->id,
        'class_id' => $this->classA->id,
        'academic_year_id' => $this->activeYear->id,
        'status' => 'active',
    ]);
    $this->oldEnrollment = Enrollment::create([
        'student_id' => $this->student->id,
        'class_id' => $this->classB->id,
        'academic_year_id' => $this->oldYear->id,
        'status' => 'completed',
    ]);

    $this->violationParent = ViolationCategory::create([
        'code' => 'V-LAP',
        'name' => 'Kedisiplinan',
        'severity' => 'ringan',
        'points' => 0,
        'status' => 'active',
    ]);
    $this->violation = ViolationCategory::create([
        'parent_id' => $this->violationParent->id,
        'code' => 'V-LAP-1',
        'name' => 'Terlambat',
        'severity' => 'ringan',
        'points' => 8,
        'status' => 'active',
    ]);
    $this->achievementParent = AchievementCategory::create([
        'code' => 'A-LAP',
        'name' => 'Kedisiplinan',
        'points' => 0,
        'status' => 'active',
    ]);
    $this->achievementCategory = AchievementCategory::create([
        'parent_id' => $this->achievementParent->id,
        'code' => 'A-LAP-1',
        'name' => 'Hadir tepat waktu',
        'points' => 10,
        'status' => 'active',
    ]);

    $this->activeCase = DisciplineCase::create([
        'case_number' => 'KAS-2026-0101',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->activeEnrollment->id,
        'class_id' => $this->classA->id,
        'violation_category_id' => $this->violation->id,
        'academic_year_id' => $this->activeYear->id,
        'reporter_id' => $this->admin->id,
        'description' => 'Kronologi tahun berjalan.',
        'status' => 'handling',
        'incident_date' => '2026-11-10',
    ]);
    $this->oldCase = DisciplineCase::create([
        'case_number' => 'KAS-2025-0099',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->oldEnrollment->id,
        'class_id' => $this->classB->id,
        'violation_category_id' => $this->violation->id,
        'academic_year_id' => $this->oldYear->id,
        'reporter_id' => $this->admin->id,
        'description' => 'Kronologi tahun sebelumnya.',
        'status' => 'dismissed',
        'incident_date' => '2026-02-10',
    ]);

    AchievementRecord::create([
        'student_id' => $this->student->id,
        'enrollment_id' => $this->activeEnrollment->id,
        'class_id' => $this->classA->id,
        'achievement_category_id' => $this->achievementCategory->id,
        'academic_year_id' => $this->activeYear->id,
        'recorded_by' => $this->admin->id,
        'status' => 'verified',
        'event_name' => 'Pembinaan ketepatan waktu',
        'event_date' => '2026-11-12',
        'points_awarded' => 10,
        'description' => 'Hadir tepat waktu sepanjang pekan.',
        'evidence_path' => 'achievement-evidence/pekan-tepat-waktu.pdf',
    ]);

    PointLedger::create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->activeYear->id,
        'direction' => 'debit',
        'amount' => 16,
        'balance_after' => 1984,
        'transaction_type' => PointLedger::TYPE_VIOLATION,
        'source_type' => DisciplineCase::class,
        'source_id' => $this->activeCase->id,
        'reason' => 'Poin berdasarkan keputusan kasus.',
        'created_by' => $this->admin->id,
        'verified_by' => $this->admin->id,
        'verified_at' => now(),
    ]);
});

it('defaults to the active year and reports the ledger amount rather than category points', function () {
    $this->actingAs($this->admin)
        ->get(route('laporan.siswa', $this->student))
        ->assertOk()
        ->assertSee('KAS-2026-0101')
        ->assertSee('Kronologi tahun berjalan.')
        ->assertSee('-16')
        ->assertDontSee('KAS-2025-0099');
});

it('supports all-year and date filters while retaining class snapshots', function () {
    $this->actingAs($this->admin)
        ->get(route('laporan.siswa', ['student' => $this->student->id, 'year' => '']))
        ->assertOk()
        ->assertSee('KAS-2026-0101')
        ->assertSee('KAS-2025-0099')
        ->assertSee('IX A');

    $this->get(route('laporan.siswa', [
        'student' => $this->student->id,
        'year' => '',
        'from' => '2026-11-01',
        'to' => '2026-11-30',
    ]))
        ->assertOk()
        ->assertSee('KAS-2026-0101')
        ->assertDontSee('KAS-2025-0099');
});

it('groups class history by snapshot and includes the linked achievement and evidence', function () {
    $this->actingAs($this->admin)
        ->get(route('laporan.kelas', ['schoolClass' => $this->classA->id, 'year' => '']))
        ->assertOk()
        ->assertSee('Siswa Uji Laporan')
        ->assertSee('KAS-2026-0101')
        ->assertSee('Pembinaan ketepatan waktu')
        ->assertSee('achievement-evidence/pekan-tepat-waktu.pdf')
        ->assertDontSee('KAS-2025-0099');
});

it('restricts homeroom reports to assigned class snapshots', function () {
    expect((int) $this->classA->fresh()->homeroom_teacher_id)->toBe($this->wali->id)
        ->and($this->student->enrollments()->where('class_id', $this->classA->id)->exists())->toBeTrue();

    $this->actingAs($this->wali)
        ->get(route('laporan.kelas', ['schoolClass' => $this->classB->id, 'year' => '']))
        ->assertForbidden();

    $this->actingAs($this->wali)
        ->get(route('laporan.siswa', ['student' => $this->student->id, 'year' => '']))
        ->assertOk()
        ->assertSee('KAS-2026-0101')
        ->assertDontSee('KAS-2025-0099');
});

it('does not expose staff reports to student accounts', function () {
    $studentUser = User::factory()->create();
    $studentUser->assignRole('siswa');

    $this->actingAs($studentUser)
        ->get(route('laporan.riwayat'))
        ->assertForbidden();
});

it('exports individual and class reports as readable XLSX rows', function () {
    foreach ([
        route('laporan.siswa.export', ['student' => $this->student->id]),
        route('laporan.kelas.export', ['schoolClass' => $this->classA->id]),
    ] as $url) {
        $response = $this->actingAs($this->admin)->get($url)->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();

        try {
            $rows = Xlsx::read($path);
            expect($rows[0])->toContain('Perubahan Poin')
                ->and(collect($rows)->flatten()->contains('-16'))->toBeTrue();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
});
