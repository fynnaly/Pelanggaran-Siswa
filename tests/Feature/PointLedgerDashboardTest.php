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
use Illuminate\Support\Carbon;
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
        'name' => 'VII 1',
        'academic_year_id' => $this->year->id,
        'grade_level' => 'VII',
        'status' => 'active',
    ]);

    $this->studentUser = User::factory()->create([
        'name' => 'Siswa Uji',
        'avatar_path' => 'avatars/siswa-uji.png',
    ]);
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

    $this->staff = User::factory()->create();
    $this->staff->assignRole('admin');
});

it('shows the five latest point entries with real directions amounts and WIB timestamps', function () {
    $transactions = [
        ['ACHIEVEMENT', 'credit', 4, 2004, 'Prestasi lama', '2026-11-15 05:29:00'],
        ['VIOLATION', 'debit', 8, 1996, 'Pelanggaran pertama', '2026-11-15 05:30:00'],
        ['VIOLATION', 'debit', 16, 1980, 'Pelanggaran kedua', '2026-11-15 05:31:00'],
        ['ACHIEVEMENT', 'credit', 10, 1990, 'Prestasi kedua', '2026-11-15 05:32:00'],
        ['VIOLATION', 'debit', 8, 1982, 'Pelanggaran terbaru', '2026-11-15 05:33:00'],
        ['ACHIEVEMENT', 'credit', 20, 2002, 'Prestasi terbaru', '2026-11-15 05:35:00'],
    ];

    foreach ($transactions as [$type, $direction, $amount, $balance, $reason, $createdAt]) {
        $entry = PointLedger::create([
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year->id,
            'direction' => $direction,
            'amount' => $amount,
            'balance_after' => $balance,
            'transaction_type' => $type,
            'reason' => $reason,
            'created_by' => $this->staff->id,
        ]);
        $entry->created_at = Carbon::parse($createdAt, 'UTC');
        $entry->save();
    }
    PointLedger::create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->year->id,
        'direction' => 'credit',
        'amount' => 2000,
        'balance_after' => 4002,
        'transaction_type' => 'OPENING_BALANCE',
        'reason' => 'Saldo Awal tidak termasuk aktivitas',
        'created_by' => $this->staff->id,
    ]);
    PointLedger::create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->year->id,
        'direction' => 'debit',
        'amount' => 20,
        'balance_after' => 3982,
        'transaction_type' => 'REVERSAL',
        'reason' => 'Reversal tidak termasuk aktivitas',
        'created_by' => $this->staff->id,
    ]);

    $response = $this->actingAs($this->studentUser)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Pelanggaran terbaru')
        ->assertSee('Prestasi terbaru')
        ->assertSee('-8')
        ->assertSee('+20')
        ->assertSee('15 Nov 2026, 12:35 WIB')
        ->assertDontSee('Prestasi lama')
        ->assertDontSee('Saldo Awal tidak termasuk aktivitas')
        ->assertDontSee('Reversal tidak termasuk aktivitas');
});

it('shows the backend profile avatar and linked case for an achievement ledger entry', function () {
    $violationCategory = ViolationCategory::create([
        'code' => 'P3.14',
        'name' => 'Mencoret seragam',
        'severity' => 'ringan',
        'points' => 8,
        'status' => 'active',
    ]);

    $achievementCategory = AchievementCategory::create([
        'code' => 'R4.2',
        'name' => 'Tidak melanggar selama tiga bulan',
        'points' => 20,
        'status' => 'active',
    ]);

    $case = DisciplineCase::create([
        'case_number' => 'KAS-2026-0004',
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'violation_category_id' => $violationCategory->id,
        'academic_year_id' => $this->year->id,
        'reporter_id' => $this->staff->id,
        'description' => 'Kasus yang ditautkan ke prestasi.',
        'status' => 'handling',
    ]);

    $record = AchievementRecord::create([
        'student_id' => $this->student->id,
        'enrollment_id' => $this->enrollment->id,
        'class_id' => $this->class->id,
        'achievement_category_id' => $achievementCategory->id,
        'discipline_case_id' => $case->id,
        'academic_year_id' => $this->year->id,
        'recorded_by' => $this->staff->id,
        'status' => 'verified',
        'event_name' => 'Penebusan kasus',
        'points_awarded' => 20,
    ]);

    $ledgerEntry = PointLedger::create([
        'student_id' => $this->student->id,
        'academic_year_id' => $this->year->id,
        'direction' => 'credit',
        'amount' => 20,
        'balance_after' => 2020,
        'transaction_type' => 'ACHIEVEMENT',
        'source_type' => AchievementRecord::class,
        'source_id' => $record->id,
        'reason' => 'Prestasi: Penebusan kasus',
        'created_by' => $this->staff->id,
    ]);
    $ledgerEntry->created_at = Carbon::parse('2026-11-15 05:35:00', 'UTC');
    $ledgerEntry->save();

    $response = $this->actingAs($this->studentUser)
        ->get(route('point-ledgers.siswa', $this->student));

    $response->assertOk()
        ->assertSee('storage/avatars/siswa-uji.png')
        ->assertSee('KAS-2026-0004')
        ->assertSee(route('kasus-pelanggaran.show', $case))
        ->assertSee('15/11/2026 12:35 WIB');
});
