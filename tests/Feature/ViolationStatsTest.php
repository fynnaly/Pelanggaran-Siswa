<?php

use App\Models\AcademicYear;
use App\Models\DisciplineCase;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Models\ViolationCategory;
use App\Support\ViolationStats;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'kesiswaan', 'guard_name' => 'web']);

    $year = AcademicYear::create(['name' => '2026/2027', 'start_date' => now(), 'is_active' => true, 'status' => 'active']);
    $this->class = SchoolClass::create(['name' => 'VII A', 'academic_year_id' => $year->id, 'grade_level' => 'VII', 'status' => 'active']);

    $mk = fn (string $code) => ViolationCategory::create([
        'code' => $code, 'name' => "Kat $code", 'group_type' => 'terlambat', 'points' => 5, 'severity' => 'ringan', 'status' => 'active',
    ]);
    $this->a = $mk('A1');
    $this->b = $mk('B1');

    $this->budi = Student::create(['full_name' => 'Budi', 'nis' => '100000000001', 'nisn' => '1000000001', 'gender' => 'L', 'status' => 'active']);
    $this->ani = Student::create(['full_name' => 'Ani', 'nis' => '100000000002', 'nisn' => '1000000002', 'gender' => 'P', 'status' => 'active']);

    $n = 0;
    $this->kasus = function (Student $s, ViolationCategory $c, string $status = 'found') use ($year, &$n) {
        return DisciplineCase::create([
            'case_number' => 'KAS-T-'.++$n, 'student_id' => $s->id, 'violation_category_id' => $c->id,
            'class_id' => $this->class->id, 'academic_year_id' => $year->id, 'status' => $status,
            'incident_date' => '2026-08-01', 'description' => 'x',
        ]);
    };
});

it('mengurutkan kategori dan siswa berdasarkan jumlah kasus, tanpa kasus dismissed', function () {
    ($this->kasus)($this->budi, $this->a);
    ($this->kasus)($this->budi, $this->a);
    ($this->kasus)($this->ani, $this->a);
    ($this->kasus)($this->ani, $this->b);
    ($this->kasus)($this->ani, $this->b, 'dismissed');
    ($this->kasus)($this->ani, $this->b, 'dismissed');

    $cats = ViolationStats::topCategories([]);
    expect($cats->pluck('code')->all())->toBe(['A1', 'B1'])
        ->and((int) $cats[0]->total)->toBe(3)
        ->and((int) $cats[0]->students)->toBe(2)
        ->and((int) $cats[1]->total)->toBe(1);

    $students = ViolationStats::topStudents([]);
    // Budi 2 kasus, Ani 2 kasus aktif (2 dismissed diabaikan): seri diurut nama.
    expect($students->pluck('full_name')->all())->toBe(['Ani', 'Budi'])
        ->and((int) $students[0]->total)->toBe(2);
});

it('halaman dashboard, laporan, dan detail kategori ter-render', function () {
    ($this->kasus)($this->budi, $this->a);
    $user = User::factory()->create();
    $user->assignRole('kesiswaan'); // role staf; kesiswaan = supervisor tanpa scope wali kelas
    $this->actingAs($user, 'web');

    $this->get(route('dashboard'))->assertOk()->assertSee('Pelanggaran Terbanyak')->assertSee('Statistik Pelanggaran');
    $this->get(route('laporan.pelanggaran'))->assertOk()->assertSee('Kat A1')->assertSee('Budi');
    $this->get(route('laporan.pelanggaran.kategori', ['violationCategory' => $this->a->id, 'year' => '', 'sort' => 'nama', 'order' => 'asc']))
        ->assertOk()->assertSee('Budi')->assertSee('KAS-T-1');
});

it('scope kosong menghasilkan data kosong, bukan tanpa filter', function () {
    ($this->kasus)($this->budi, $this->a);

    expect(ViolationStats::topCategories(['scope' => []]))->toBeEmpty()
        ->and(ViolationStats::topCategories(['scope' => [$this->class->id]]))->toHaveCount(1);
});
