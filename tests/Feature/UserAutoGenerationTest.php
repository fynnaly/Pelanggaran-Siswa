<?php

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['admin', 'guru', 'wali_kelas', 'bk', 'siswa', 'kesiswaan', 'kepala_sekolah'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('generates accounts and random passwords for active students without an account', function () {
    $student = Student::create([
        'nisn' => '0010000001',
        'nis' => '202412010001',
        'full_name' => 'Siswa Satu',
        'gender' => 'L',
        'status' => 'active',
    ]);
    $studentDua = Student::create([
        'nisn' => '0010000002',
        'nis' => '202412010002',
        'full_name' => 'Siswa Dua',
        'gender' => 'P',
        'status' => 'active',
    ]);
    $inactive = Student::create([
        'nisn' => '0010000003',
        'nis' => '202412010003',
        'full_name' => 'Siswa Nonaktif',
        'gender' => 'L',
        'status' => 'inactive',
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('users.generate'), ['type' => 'siswa'])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('generated_accounts');

    $generated = session('generated_accounts');
    expect($generated)->toHaveCount(2);

    $account = User::where('email', '202412010001@smpbn666.sch.id')->first();

    expect($account)->not->toBeNull()
        ->and($account->name)->toBe('Siswa Satu')
        ->and($account->hasRole('siswa'))->toBeTrue()
        ->and($student->fresh()->user_id)->toBe($account->id)
        ->and($studentDua->fresh()->user_id)->not->toBeNull()
        ->and($inactive->fresh()->user_id)->toBeNull();

    $credential = collect($generated)->firstWhere('email', $account->email);
    expect(Hash::check($credential['password'], $account->password))->toBeTrue();

    // Cost lebih rendah dipakai agar generate massal tidak timeout; hash tetap valid
    // dan akan otomatis di-rehash ke cost normal saat login pertama.
    expect(password_get_info($account->password)['options']['cost'])->toBeLessThanOrEqual(8);
});

it('generates teacher accounts using the teacher name and skips teachers that already have one', function () {
    $teacher = Teacher::create([
        'nip' => '198501012010011001',
        'full_name' => 'Budi Guru',
        'mata_pelajaran' => 'Matematika',
        'status' => 'active',
    ]);

    $linkedUser = User::factory()->create();
    $linkedUser->assignRole('guru');
    Teacher::create([
        'user_id' => $linkedUser->id,
        'nip' => '198501012010011002',
        'full_name' => 'Guru Sudah Punya Akun',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->post(route('users.generate'), ['type' => 'guru'])
        ->assertRedirect(route('users.index'));

    $generated = session('generated_accounts');
    expect($generated)->toHaveCount(1);

    $account = User::where('email', 'budiguru@smpbn666.sch.id')->first();

    expect($account)->not->toBeNull()
        ->and($account->hasRole('guru'))->toBeTrue()
        ->and($account->nip)->toBe('198501012010011001')
        ->and($teacher->fresh()->user_id)->toBe($account->id);
});

it('maps structural teacher positions to roles when generating accounts', function () {
    Teacher::create(['full_name' => 'Kepala Sekolah Satu', 'jabatan' => 'Kepala Sekolah', 'status' => 'active']);
    Teacher::create(['full_name' => 'Wakil Kesiswaan Dua', 'jabatan' => 'Wks. Kesiswaan', 'status' => 'active']);
    Teacher::create(['full_name' => 'Wakil Kurikulum Tiga', 'jabatan' => 'Wks. Kurikulum', 'status' => 'active']);
    Teacher::create(['full_name' => 'Guru BK Empat', 'jabatan' => 'bk', 'status' => 'active']);

    $this->actingAs($this->admin)
        ->post(route('users.generate'), ['type' => 'guru'])
        ->assertRedirect(route('users.index'));

    expect(User::where('email', 'kepalasekolahsatu@smpbn666.sch.id')->firstOrFail()->hasRole('kepala_sekolah'))->toBeTrue()
        ->and(User::where('email', 'wakilkesiswaandua@smpbn666.sch.id')->firstOrFail()->hasRole('kesiswaan'))->toBeTrue()
        ->and(User::where('email', 'wakilkurikulumtiga@smpbn666.sch.id')->firstOrFail()->hasRole('guru'))->toBeTrue()
        ->and(User::where('email', 'gurubkempat@smpbn666.sch.id')->firstOrFail()->hasRole('bk'))->toBeTrue();
});

it('refuses to generate accounts for non-management roles', function () {
    $guru = User::factory()->create();
    $guru->assignRole('guru');

    Student::create([
        'nisn' => '0010000004',
        'nis' => '202412010004',
        'full_name' => 'Siswa Tanpa Akun',
        'gender' => 'L',
        'status' => 'active',
    ]);

    $this->actingAs($guru)
        ->post(route('users.generate'), ['type' => 'siswa'])
        ->assertForbidden();

    expect(Student::whereNull('user_id')->count())->toBe(1);
});

it('downloads the freshly generated credentials as csv', function () {
    Student::create([
        'nisn' => '0010000005',
        'nis' => '202412010005',
        'full_name' => 'Siswa Unduh',
        'gender' => 'L',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->post(route('users.generate'), ['type' => 'siswa']);

    $response = $this->actingAs($this->admin)
        ->get(route('users.generate.download'))
        ->assertOk();

    $csv = $response->streamedContent();

    expect($csv)->toContain('Nama,Kelas,Email,Password')
        ->and($csv)->toContain('202412010005@smpbn666.sch.id')
        ->and($csv)->toContain('Siswa Unduh');
});

it('keeps generated credentials downloadable after the redirect page is opened', function () {
    Student::create([
        'nisn' => '0010000006',
        'nis' => '2425.7.001',
        'full_name' => 'Siswa Simpan',
        'gender' => 'L',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->post(route('users.generate'), ['type' => 'siswa'])
        ->assertRedirect(route('users.index'));

    // Halaman redirect dibuka dulu — flash data habis di sini.
    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('Kredensial Akun');

    $response = $this->actingAs($this->admin)
        ->get(route('users.generate.download'))
        ->assertOk();

    $csv = $response->streamedContent();

    expect($csv)->toContain('24257001@smpbn666.sch.id')
        ->and($csv)->toContain('Siswa Simpan');
});

it('clears the stored credentials from the session on request', function () {
    Student::create([
        'nisn' => '0010000007',
        'nis' => '202412010007',
        'full_name' => 'Siswa Hapus',
        'gender' => 'L',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->post(route('users.generate'), ['type' => 'siswa']);

    $this->actingAs($this->admin)
        ->post(route('users.generate.clear'))
        ->assertRedirect(route('users.index'));

    expect(session('generated_credentials'))->toBeNull();

    $this->actingAs($this->admin)
        ->get(route('users.generate.download'))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('error');
});

it('reports when there is nothing to generate', function () {
    $this->actingAs($this->admin)
        ->post(route('users.generate'), ['type' => 'siswa'])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('error');
});

it('normalizes legacy auto account emails to the new domain', function () {
    $studentUser = User::factory()->create(['email' => 'siswa.24257001@sekolah.sch.id']);
    $studentUser->assignRole('siswa');

    $teacherUser = User::factory()->create(['email' => 'guru.198501012010011001@sekolah.sch.id']);
    $teacherUser->assignRole('guru');

    $this->artisan('accounts:normalize-emails', ['--apply' => true])->assertSuccessful();

    expect($studentUser->fresh()->email)->toBe('24257001@smpbn666.sch.id')
        ->and($teacherUser->fresh()->email)->toBe('guru.198501012010011001@smpbn666.sch.id');
});

it('only simulates email normalization unless --apply is given', function () {
    $user = User::factory()->create(['email' => 'siswa.24257002@sekolah.sch.id']);
    $user->assignRole('siswa');

    $this->artisan('accounts:normalize-emails')->assertSuccessful();

    expect($user->fresh()->email)->toBe('siswa.24257002@sekolah.sch.id');
});
