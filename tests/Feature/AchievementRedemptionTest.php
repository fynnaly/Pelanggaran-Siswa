<?php

use App\Models\AcademicYear;
use App\Models\AchievementCategory;
use App\Models\DisciplineCase;
use App\Models\Enrollment;
use App\Models\RedemptionRule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Models\ViolationCategory;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['admin', 'siswa'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    }

    $this->year = AcademicYear::create([
        'name' => '2026/2027',
        'start_date' => '2026-07-01',
        'is_active' => true,
        'status' => 'active',
    ]);

    $this->class = SchoolClass::create([
        'name' => 'X PPLG 1',
        'academic_year_id' => $this->year->id,
        'grade_level' => 'X',
        'status' => 'active',
    ]);

    $this->studentUser = User::factory()->create(['name' => 'Siswa Uji']);
    $this->studentUser->assignRole('siswa');

    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'full_name' => 'Siswa Uji',
        'nis' => '123456789012',
        'nisn' => '1234567890',
        'gender' => 'L',
        'status' => 'active',
    ]);

    $this->enrollment = Enrollment::create([
        'student_id' => $this->student->id,
        'class_id' => $this->class->id,
        'academic_year_id' => $this->year->id,
        'status' => 'active',
    ]);

    $this->admin = User::factory()->create(['name' => 'Admin Uji']);
    $this->admin->assignRole('admin');

    $violationParent = ViolationCategory::create([
        'code' => 'P3',
        'name' => 'Kebersihan',
        'severity' => 'ringan',
        'points' => 0,
        'status' => 'active',
    ]);

    $this->violation = ViolationCategory::create([
        'parent_id' => $violationParent->id,
        'code' => 'P3.14',
        'name' => 'Mencoret seragam',
        'severity' => 'ringan',
        'points' => 16,
        'status' => 'active',
    ]);

    $this->otherViolation = ViolationCategory::create([
        'parent_id' => $violationParent->id,
        'code' => 'P3.15',
        'name' => 'Membuang sampah sembarangan',
        'severity' => 'ringan',
        'points' => 8,
        'status' => 'active',
    ]);

    $this->achievementParent = AchievementCategory::create([
        'code' => 'R9',
        'name' => 'Kebersihan',
        'points' => 0,
        'status' => 'active',
    ]);

    $this->matchingAchievement = AchievementCategory::create([
        'parent_id' => $this->achievementParent->id,
        'code' => 'R9.1',
        'name' => 'Menunjukkan sikap disiplin',
        'points' => 10,
        'status' => 'active',
    ]);

    $differentAchievementParent = AchievementCategory::create([
        'code' => 'R8',
        'name' => 'Kegiatan Akademik',
        'points' => 0,
        'status' => 'active',
    ]);

    $this->unmatchedAchievement = AchievementCategory::create([
        'parent_id' => $differentAchievementParent->id,
        'code' => 'R9.2',
        'name' => 'Berpartisipasi kegiatan sekolah',
        'points' => 20,
        'status' => 'active',
    ]);
});

it('allows zero-point parent categories and manages matching automatically by parent name', function () {
    $this->actingAs($this->admin);

    $this->get(route('achievement-categories.create'))
        ->assertOk()
        ->assertSee('dengan nama kategori induk yang sama akan dipadankan otomatis')
        ->assertDontSee('name="violation_category_ids[]"', false);

    $this->post(route('achievement-categories.store'), [
        'code' => 'R10',
        'name' => 'Kategori Pengembangan',
        'parent_id' => '',
        'points' => 0,
        'status' => 'active',
    ])->assertRedirect(route('achievement-categories.index'));

    $parent = AchievementCategory::where('code', 'R10')->firstOrFail();
    expect($parent->points)->toBe(0);

    $this->post(route('achievement-categories.store'), [
        'code' => 'R10.1',
        'name' => 'Kebiasaan baik',
        'parent_id' => $parent->id,
        'points' => 10,
        'status' => 'active',
    ])->assertRedirect(route('achievement-categories.index'));

    $category = AchievementCategory::where('code', 'R10.1')->firstOrFail();
    $legacyRule = RedemptionRule::create([
        'violation_category_id' => $this->violation->id,
        'achievement_category_id' => $category->id,
        'poin_penebus' => 7,
    ]);

    $this->put(route('achievement-categories.update', $category), [
        'code' => $category->code,
        'name' => 'Kebiasaan baik yang konsisten',
        'parent_id' => $parent->id,
        'points' => 10,
        'status' => 'active',
    ])->assertRedirect(route('achievement-categories.index'));

    expect($category->fresh()->name)->toBe('Kebiasaan baik yang konsisten');
    expect($legacyRule->fresh()->poin_penebus)->toBe(7);

    $this->put(route('achievement-categories.update', $category), [
        'name' => $category->name,
    ])->assertRedirect(route('achievement-categories.index'));

    $this->assertDatabaseHas('redemption_rules', [
        'achievement_category_id' => $category->id,
        'violation_category_id' => $this->violation->id,
    ]);

    $this->put(route('achievement-categories.update', $parent), [
        'parent_id' => $this->achievementParent->id,
    ])->assertSessionHasErrors('parent_id');
});

it('offers achievement categories with the same parent name and rejects mismatched categories', function () {
    $this->actingAs($this->admin);

    $case = DisciplineCase::create([
        'case_number' => 'KAS-2026-0004',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->violation->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $this->admin->id,
        'description' => 'Kasus uji untuk penebusan.',
        'status' => 'handling',
        'incident_date' => '2026-11-14',
    ]);

    $this->get(route('achievement-records.open-cases', $this->student))
        ->assertOk()
        ->assertJsonPath('0.required_achievement.0.id', $this->matchingAchievement->id)
        ->assertJsonCount(1, '0.required_achievement');

    $this->get(route('achievement-records.create'))
        ->assertOk()
        ->assertSee('R9.1')
        ->assertSee('eligible.length===1', false);

    $this->post(route('achievement-records.store'), [
        'student_id' => $this->student->id,
        'achievement_category_id' => $this->unmatchedAchievement->id,
        'discipline_case_id' => $case->id,
        'event_name' => 'Pemulihan sikap',
    ])->assertSessionHasErrors('achievement_category_id');

    $this->assertDatabaseMissing('achievement_records', [
        'student_id' => $this->student->id,
        'discipline_case_id' => $case->id,
    ]);

    $this->post(route('achievement-records.store'), [
        'student_id' => $this->student->id,
        'achievement_category_id' => $this->matchingAchievement->id,
        'discipline_case_id' => $case->id,
        'event_name' => 'Pemulihan sikap',
    ])->assertRedirect();

    $this->assertDatabaseHas('achievement_records', [
        'student_id' => $this->student->id,
        'achievement_category_id' => $this->matchingAchievement->id,
        'discipline_case_id' => $case->id,
    ]);
});

it('does not offer inactive or expired achievement categories as automatic matches', function () {
    $this->actingAs($this->admin);

    $inactive = AchievementCategory::create([
        'parent_id' => $this->achievementParent->id,
        'code' => 'R9.3',
        'name' => 'Prestasi tidak aktif',
        'points' => 5,
        'status' => 'draft',
    ]);

    $expired = AchievementCategory::create([
        'parent_id' => $this->achievementParent->id,
        'code' => 'R9.4',
        'name' => 'Prestasi kedaluwarsa',
        'points' => 5,
        'status' => 'active',
        'expiry_date' => now()->subDay()->toDateString(),
    ]);

    DisciplineCase::create([
        'case_number' => 'KAS-2026-0005',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->violation->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $this->admin->id,
        'description' => 'Kasus uji untuk kategori tidak aktif.',
        'status' => 'handling',
        'incident_date' => '2026-11-14',
    ]);

    $this->get(route('achievement-records.open-cases', $this->student))
        ->assertOk()
        ->assertJsonCount(1, '0.required_achievement')
        ->assertJsonPath('0.required_achievement.0.id', $this->matchingAchievement->id);

    expect($inactive->status)->toBe('draft')
        ->and($expired->expiry_date->isPast())->toBeTrue();
});
