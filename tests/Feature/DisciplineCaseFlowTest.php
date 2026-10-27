<?php

use App\Models\AcademicYear;
use App\Models\DisciplineCase;
use App\Models\Enrollment;
use App\Models\PointLedger;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Models\ViolationCategory;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'bk', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);

    $this->year = AcademicYear::create([
        'name' => '2026/2027',
        'start_date' => now(),
        'is_active' => true,
        'status' => 'active',
    ]);

    $this->class = SchoolClass::create([
        'name' => 'X PPLG 1',
        'academic_year_id' => $this->year->id,
        'grade_level' => 'X',
        'status' => 'active',
    ]);

    $this->student = Student::create([
        'full_name' => 'Budi Santoso',
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

    $this->parentCategory = ViolationCategory::create([
        'code' => 'P1',
        'name' => 'Terlambat',
        'group_type' => 'terlambat',
        'points' => 0,
        'status' => 'active',
    ]);

    $this->category = ViolationCategory::create([
        'parent_id' => $this->parentCategory->id,
        'code' => 'P1.1',
        'name' => 'Kurang dari 10 menit',
        'group_type' => 'terlambat',
        'points' => 8,
        'severity' => 'ringan',
        'status' => 'active',
    ]);

    $this->bkUser = User::factory()->create();
    $this->bkUser->assignRole('bk');
    $this->actingAs($this->bkUser, 'web');
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
});

it('creates case with initial found status and does not deduct points yet', function () {
    $this->actingAs($this->bkUser);

    $response = $this->post(route('kasus-pelanggaran.store'), [
        'student_id' => $this->student->id,
        'violation_category_id' => $this->category->id,
        'class_id' => $this->class->id,
        'description' => 'Terlambat 5 menit saat upacara.',
    ]);

    $response->assertRedirect(route('kasus-pelanggaran.index'));

    $case = DisciplineCase::first();
    expect($case)->not->toBeNull();
    expect($case->status)->toBe('found');
    expect(PointLedger::count())->toBe(0);
});

it('deducts points when case is transitioned to handling', function () {
    $this->actingAs($this->bkUser);

    $case = DisciplineCase::create([
        'case_number' => 'KAS-2026-0001',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->category->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $this->bkUser->id,
        'description' => 'Terlambat 5 menit.',
        'status' => 'found',
    ]);

    $response = $this->patch(route('kasus-pelanggaran.update-status', $case), [
        'status' => 'handling',
        'notes' => 'Siswa dipanggil dan diberikan teguran.',
    ]);

    $response->assertRedirect(route('kasus-pelanggaran.show', $case));

    $case->refresh();
    expect($case->status)->toBe('handling');
    expect($case->validator_id)->toBe($this->bkUser->id);
    expect($case->validated_at)->not->toBeNull();

    // Pastikan debit poin dibuat (8 poin dari saldo awal 2000 => 1992)
    $ledger = PointLedger::where('source_id', $case->id)->first();
    expect($ledger)->not->toBeNull();
    expect($ledger->amount)->toBe(8);
    expect($ledger->balance_after)->toBe(1992);

    // Transisi lanjutan ke closed tidak memotong poin dua kali
    $response2 = $this->patch(route('kasus-pelanggaran.update-status', $case), [
        'status' => 'closed',
    ]);
    $response2->assertRedirect(route('kasus-pelanggaran.show', $case));

    $case->refresh();
    expect($case->status)->toBe('closed');
    expect($case->closed_at)->not->toBeNull();
    expect(PointLedger::where('source_id', $case->id)->count())->toBe(1);
});

it('does not deduct points when case is dismissed from found', function () {
    $this->actingAs($this->bkUser);

    $case = DisciplineCase::create([
        'case_number' => 'KAS-2026-0002',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->category->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $this->bkUser->id,
        'description' => 'Salah lapor.',
        'status' => 'found',
    ]);

    $response = $this->patch(route('kasus-pelanggaran.dismiss', $case));
    $response->assertRedirect(route('kasus-pelanggaran.index'));

    $case->refresh();
    expect($case->status)->toBe('dismissed');
    expect(PointLedger::count())->toBe(0);
});

it('supports bulk status progression from found to handling and handling to closed', function () {
    $this->actingAs($this->bkUser);

    $c1 = DisciplineCase::create([
        'case_number' => 'KAS-2026-0003',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->category->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $this->bkUser->id,
        'description' => 'Kasus 1',
        'status' => 'found',
    ]);

    $c2 = DisciplineCase::create([
        'case_number' => 'KAS-2026-0004',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->category->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $this->bkUser->id,
        'description' => 'Kasus 2',
        'status' => 'found',
    ]);

    // Bulk majukan ke handling (potong poin 2 kasus)
    $res = $this->post(route('kasus-pelanggaran.bulk-status'), [
        'ids' => [$c1->id, $c2->id],
        'status' => 'handling',
    ]);
    $res->assertSessionHas('success');

    $c1->refresh();
    $c2->refresh();
    expect($c1->status)->toBe('handling');
    expect($c2->status)->toBe('handling');
    expect(PointLedger::count())->toBe(2);

    // Bulk majukan ke closed
    $res2 = $this->post(route('kasus-pelanggaran.bulk-status'), [
        'ids' => [$c1->id, $c2->id],
        'status' => 'closed',
    ]);
    $res2->assertSessionHas('success');

    $c1->refresh();
    $c2->refresh();
    expect($c1->status)->toBe('closed');
    expect($c2->status)->toBe('closed');
    // Poin tetap 2 debit (tidak bertambah lagi)
    expect(PointLedger::count())->toBe(2);
});
