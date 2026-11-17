<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['admin', 'guru', 'wali_kelas', 'bk', 'siswa', 'kesiswaan', 'kepala_sekolah'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    }

    $this->year = AcademicYear::create([
        'name' => '2026/2027',
        'start_date' => now(),
        'is_active' => true,
        'status' => 'active',
    ]);

    $this->guruUser = User::factory()->create(['name' => 'Budi Guru, S.Pd.']);
    $this->guruUser->assignRole('guru');

    $this->class = SchoolClass::create([
        'name' => 'VII 1',
        'academic_year_id' => $this->year->id,
        'grade_level' => 'VII',
        'status' => 'active',
        'homeroom_teacher_id' => $this->guruUser->id,
    ]);

    $this->siswaUser = User::factory()->create(['name' => 'Ahmad Siswa']);
    $this->siswaUser->assignRole('siswa');

    $this->student = Student::create([
        'user_id' => $this->siswaUser->id,
        'full_name' => 'Ahmad Siswa',
        'nis' => 'NIS-888',
        'nisn' => '0088888888',
        'gender' => 'L',
        'status' => 'active',
    ]);

    $this->enrollment = Enrollment::create([
        'student_id' => $this->student->id,
        'class_id' => $this->class->id,
        'academic_year_id' => $this->year->id,
        'status' => 'active',
    ]);

    $this->adminUser = User::factory()->create(['name' => 'Administrator']);
    $this->adminUser->assignRole('admin');
});

it('renders student dashboard with school-oriented character terms and without corporate jargon', function () {
    $this->actingAs($this->siswaUser);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertViewIs('dashboard-siswa');

    // Memverifikasi teks edukatif dan ramah siswa
    $response->assertSee('Ahmad Siswa');
    $response->assertSee('VII 1');
    $response->assertSee('Poin Karakter dan Prestasi');
    $response->assertSee('Jenjang Sikap');
    $response->assertSee('Lihat Riwayat Poin');
    $response->assertSee('Catatan Kedisiplinan');
    $response->assertSee('Pencapaian dan Prestasi');
    $response->assertSee('Standar Baik (1900-2199)');
    $response->assertSee('Berprestasi (2200+)');
    $response->assertSee('Panduan Karakter: Cara Menambah dan Memulihkan Poinmu');

    // Memverifikasi tidak ada diksi korporat/audit
    $response->assertDontSee('Tingkat Kepatuhan');
    $response->assertDontSee('Kritis (<1500)');
    $response->assertDontSee('Waspada (1500-1799)');
    $response->assertDontSee('Aman (1800+)');
    $response->assertDontSee('Saldo Poin Disiplin');
    $response->assertDontSee('Buku Kas Poin');
    $response->assertDontSee('Mutasi Poin Terakhir');
});

it('routes guru to teacher dashboard and admin to executive school dashboard', function () {
    $this->actingAs($this->guruUser);
    $responseGuru = $this->get(route('dashboard'));
    $responseGuru->assertOk();
    $responseGuru->assertViewIs('dashboard-guru');

    $this->actingAs($this->adminUser);
    $responseAdmin = $this->get(route('dashboard'));
    $responseAdmin->assertOk();
    $responseAdmin->assertViewIs('dashboard');
});

it('forbids siswa from accessing management and master data', function () {
    $this->actingAs($this->siswaUser);

    $this->get('/users')->assertForbidden();
    $this->get('/settings')->assertForbidden();
    $this->get('/classes')->assertForbidden();
    $this->get('/academic-years')->assertForbidden();
    $this->get('/students')->assertForbidden();
    $this->get('/laporan/pelanggaran')->assertForbidden();
});

it('denies user without role from accessing internal features', function () {
    $userWithoutRole = User::factory()->create();
    $this->actingAs($userWithoutRole);

    $this->get(route('kasus-pelanggaran.index'))->assertForbidden();
    $this->get(route('achievement-records.index'))->assertForbidden();
    $this->get(route('point-ledgers.index'))->assertForbidden();
    $this->get(route('monitor-poin.index'))->assertForbidden();
    $this->get(route('students.index'))->assertForbidden();
});
