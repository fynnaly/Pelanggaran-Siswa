<?php

use App\Models\AcademicYear;
use App\Models\DisciplineCase;
use App\Models\Enrollment;
use App\Models\PointLedger;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Models\ViolationCategory;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

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

    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole('admin');
    $this->actingAs($this->adminUser, 'web');
    $this->withoutMiddleware(PreventRequestForgery::class);
});

it('creates case with initial found status and does not deduct points yet', function () {
    $this->actingAs($this->adminUser);

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
    $this->actingAs($this->adminUser);

    $case = DisciplineCase::create([
        'case_number' => 'KAS-2026-0001',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->category->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $this->adminUser->id,
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
    expect($case->validator_id)->toBe($this->adminUser->id);
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
    $this->actingAs($this->adminUser);

    $case = DisciplineCase::create([
        'case_number' => 'KAS-2026-0002',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->category->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $this->adminUser->id,
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
    $this->actingAs($this->adminUser);

    $c1 = DisciplineCase::create([
        'case_number' => 'KAS-2026-0003',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->category->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $this->adminUser->id,
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
        'reporter_id' => $this->adminUser->id,
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

it('allows bk and kesiswaan to update and dismiss discipline case but forbids guru', function () {
    $bk = User::factory()->create();
    $bk->assignRole('bk');

    Role::firstOrCreate(['name' => 'kesiswaan', 'guard_name' => 'web']);
    $kesiswaan = User::factory()->create();
    $kesiswaan->assignRole('kesiswaan');

    Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
    $guru = User::factory()->create();
    $guru->assignRole('guru');

    $case = DisciplineCase::create([
        'case_number' => 'KAS-2026-0005',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->category->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $guru->id,
        'description' => 'Laporan temuan guru',
        'status' => 'found',
    ]);

    // Guru hanya bisa melapor, dilarang validasi / ubah status / dismiss
    $this->actingAs($guru);
    $this->patch(route('kasus-pelanggaran.update-status', $case), ['status' => 'handling'])->assertForbidden();
    $this->patch(route('kasus-pelanggaran.dismiss', $case))->assertForbidden();
    $this->post(route('kasus-pelanggaran.bulk-status'), ['ids' => [$case->id], 'status' => 'handling'])->assertForbidden();

    // BK dan Kesiswaan berwenang memajukan / memvalidasi status
    $this->actingAs($bk);
    $this->patch(route('kasus-pelanggaran.update-status', $case), ['status' => 'handling'])->assertRedirect();
    $case->refresh();
    expect($case->status)->toBe('handling');
});

it('scopes discipline cases and point history for siswa role', function () {
    Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);
    $siswaUser = User::factory()->create();
    $siswaUser->assignRole('siswa');
    $this->student->update(['user_id' => $siswaUser->id]);

    $otherStudent = Student::create([
        'full_name' => 'Siswa Lain',
        'nis' => '999999999999',
        'nisn' => '9999999999',
        'gender' => 'P',
        'status' => 'active',
    ]);

    $caseMy = DisciplineCase::create([
        'case_number' => 'KAS-MY-001',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->category->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $this->adminUser->id,
        'description' => 'Pelanggaran saya',
        'status' => 'found',
        'incident_date' => '2026-11-12',
        'incident_time' => '07:30',
    ]);
    $caseMy->created_at = Carbon::parse('2026-08-14 21:37:00', 'UTC');
    $caseMy->save();

    $caseOther = DisciplineCase::create([
        'case_number' => 'KAS-OTHER-002',
        'student_id' => $otherStudent->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $this->category->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $this->adminUser->id,
        'description' => 'Pelanggaran orang lain',
        'status' => 'found',
        'incident_date' => '2026-11-12',
        'incident_time' => '08:00',
    ]);

    $this->actingAs($siswaUser);

    // Siswa melihat indeks kasus disiplin: hanya kasus dirinya sendiri yang tampil
    $response = $this->get(route('kasus-pelanggaran.index'));
    $response->assertOk();
    $response->assertSee('KAS-MY-001');
    $response->assertDontSee('KAS-OTHER-002');
    $response->assertSee('07:30 WIB');

    // Siswa bisa melihat rincian kasusnya sendiri
    $this->get(route('kasus-pelanggaran.show', $caseMy))
        ->assertOk()
        ->assertSee('07:30 WIB')
        ->assertSee('15 Agt 2026 04:37 WIB');

    // Siswa ditolak jika mencoba mengintip rincian kasus siswa lain
    $this->get(route('kasus-pelanggaran.show', $caseOther))->assertForbidden();

    // Siswa ditolak jika mencoba melihat buku kas siswa lain
    $this->get(route('point-ledgers.siswa', $otherStudent))->assertForbidden();

    // Siswa membuka buku kas miliknya sendiri berhasil
    $this->get(route('point-ledgers.siswa', $this->student))->assertOk();
});

it('limits guru and wali kelas to cases they reported', function () {
    foreach (['guru', 'wali_kelas'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        $reporter = User::factory()->create();
        $reporter->assignRole($roleName);
        $this->class->update(['homeroom_teacher_id' => $reporter->id]);

        $ownCase = DisciplineCase::create([
            'case_number' => 'KAS-OWN-'.$roleName,
            'student_id' => $this->student->id,
            'enrollment_id' => $this->enrollment->id,
            'class_id' => $this->class->id,
            'violation_category_id' => $this->category->id,
            'academic_year_id' => $this->year->id,
            'reporter_id' => $reporter->id,
            'description' => 'Kasus yang dilaporkan sendiri',
            'status' => 'found',
        ]);

        $otherCase = DisciplineCase::create([
            'case_number' => 'KAS-OTHER-'.$roleName,
            'student_id' => $this->student->id,
            'enrollment_id' => $this->enrollment->id,
            'class_id' => $this->class->id,
            'violation_category_id' => $this->category->id,
            'academic_year_id' => $this->year->id,
            'reporter_id' => $this->adminUser->id,
            'description' => 'Kasus yang dilaporkan orang lain',
            'status' => 'found',
        ]);

        $this->actingAs($reporter)
            ->get(route('kasus-pelanggaran.index'))
            ->assertOk()
            ->assertSee($ownCase->case_number)
            ->assertDontSee($otherCase->case_number);

        $this->get(route('kasus-pelanggaran.show', $ownCase))->assertOk();
        $this->get(route('kasus-pelanggaran.show', $otherCase))->assertForbidden();
    }

    $this->actingAs($this->adminUser)
        ->get(route('kasus-pelanggaran.index'))
        ->assertSee('KAS-OTHER-guru')
        ->assertSee('KAS-OTHER-wali_kelas');
});
