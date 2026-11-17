<?php

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Enrollment;
use App\Models\SchoolClass;
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

    $this->year = AcademicYear::create([
        'name' => '2026/2027',
        'start_date' => now()->startOfYear(),
        'is_active' => true,
        'status' => 'active',
    ]);

    $this->classA = SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'name' => 'VII A',
        'grade_level' => 'VII',
        'angkatan' => 2026,
        'status' => 'active',
    ]);

    $this->classB = SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'name' => 'VIII B',
        'grade_level' => 'VIII',
        'angkatan' => 2025,
        'status' => 'active',
    ]);
});

function studentAccount(string $nis, string $name, SchoolClass $class, AcademicYear $year): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->assignRole('siswa');

    $student = Student::create([
        'user_id' => $user->id,
        'nisn' => str_pad(substr(preg_replace('/\D/', '', $nis), 0, 8), 10, '0', STR_PAD_LEFT),
        'nis' => $nis,
        'full_name' => $name,
        'gender' => 'L',
        'status' => 'active',
    ]);

    Enrollment::create([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'academic_year_id' => $year->id,
        'status' => 'active',
        'enrolled_at' => now(),
    ]);

    return $user;
}

function teacherAccount(string $nip, string $name): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->assignRole('guru');

    Teacher::create([
        'user_id' => $user->id,
        'nip' => $nip,
        'full_name' => $name,
        'status' => 'active',
    ]);

    return $user;
}

it('resets every student password in bulk and never logs the plain text password', function () {
    $a = studentAccount('2425.7.001', 'Siswa Kelas A', $this->classA, $this->year);
    $b = studentAccount('2425.7.002', 'Siswa Kelas A Dua', $this->classA, $this->year);
    $guru = teacherAccount('198501012010011001', 'Guru Satu');
    $guru->createToken('api');

    $this->actingAs($this->admin)
        ->post(route('users.passwords.reset'), ['type' => 'siswa', 'scope' => 'all'])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('generated_accounts', fn ($accounts) => count($accounts) === 2);

    $accounts = session('generated_accounts');

    foreach ($accounts as $account) {
        $user = User::where('email', $account['email'])->firstOrFail();

        expect($account['password'])->toHaveLength(6)
            ->and($account['password'])->toMatch('/^\d{6}$/')
            ->and(Hash::check($account['password'], $user->password))->toBeTrue()
            ->and(Hash::check('password', $user->password))->toBeFalse();
    }

    // Guru tidak tersentuh karena tipe dibatasi ke siswa/guru.
    expect(Hash::check('password', $guru->fresh()->password))->toBeTrue()
        ->and($guru->tokens()->count())->toBe(1);

    $log = ActivityLog::where('action', 'user.password_reset')->firstOrFail();
    $raw = json_encode($log->properties);

    expect($log->properties['total'])->toBe(2)
        ->and($raw)->not->toContain($accounts[0]['password'])
        ->and($raw)->not->toContain($accounts[1]['password']);
});

it('resets every active account for the all-users type but skips the acting admin', function () {
    $siswa = studentAccount('2425.7.001', 'Siswa Semua', $this->classA, $this->year);
    $guru = teacherAccount('198501012010011009', 'Guru Semua');
    $bk = User::factory()->create(['name' => 'BK Semua']);
    $bk->assignRole('bk');

    $this->actingAs($this->admin)
        ->post(route('users.passwords.reset'), ['type' => 'all', 'scope' => 'all'])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('generated_accounts', fn ($accounts) => count($accounts) === 3);

    expect(Hash::check('password', $this->admin->fresh()->password))->toBeTrue()
        ->and(Hash::check('password', $siswa->fresh()->password))->toBeFalse()
        ->and(Hash::check('password', $guru->fresh()->password))->toBeFalse()
        ->and(Hash::check('password', $bk->fresh()->password))->toBeFalse();

    // Role di daftar kredensial memakai role asli tiap akun, bukan "all".
    $roles = collect(session('generated_accounts'))->pluck('role', 'email');

    expect($roles[$siswa->email])->toBe('siswa')
        ->and($roles[$guru->email])->toBe('guru')
        ->and($roles[$bk->email])->toBe('bk');
});

it('resets passwords only for the selected class', function () {
    $a = studentAccount('2425.7.001', 'Siswa Kelas A', $this->classA, $this->year);
    $b = studentAccount('2425.8.001', 'Siswa Kelas B', $this->classB, $this->year);

    $this->actingAs($this->admin)
        ->post(route('users.passwords.reset'), [
            'type' => 'siswa',
            'scope' => 'class',
            'class_id' => $this->classA->id,
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('generated_accounts', fn ($accounts) => count($accounts) === 1
            && $accounts[0]['email'] === $a->email);

    expect(Hash::check('password', $a->fresh()->password))->toBeFalse()
        ->and(Hash::check('password', $b->fresh()->password))->toBeTrue();
});

it('resets passwords only for the selected tingkatan', function () {
    $a = studentAccount('2425.7.001', 'Siswa Tingkatan VII', $this->classA, $this->year);
    $b = studentAccount('2425.8.001', 'Siswa Tingkatan VIII', $this->classB, $this->year);

    $this->actingAs($this->admin)
        ->post(route('users.passwords.reset'), [
            'type' => 'siswa',
            'scope' => 'grade',
            'grade_level' => 'VIII',
        ])
        ->assertSessionHas('generated_accounts', fn ($accounts) => count($accounts) === 1
            && $accounts[0]['email'] === $b->email);

    expect(Hash::check('password', $a->fresh()->password))->toBeTrue()
        ->and(Hash::check('password', $b->fresh()->password))->toBeFalse();
});

it('downloads reset passwords as csv after the redirect page is opened', function () {
    studentAccount('2425.7.001', 'Siswa Kelas A', $this->classA, $this->year);

    $this->actingAs($this->admin)
        ->post(route('users.passwords.reset'), ['type' => 'siswa', 'scope' => 'all'])
        ->assertRedirect(route('users.index'));

    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('Kredensial Akun');

    $response = $this->actingAs($this->admin)
        ->get(route('users.generate.download'))
        ->assertOk();

    expect($response->streamedContent())->toContain('Siswa Kelas A');
});

it('names the csv after the reset scope and includes the class column', function () {
    studentAccount('2425.7.001', 'Siswa Kelas A', $this->classA, $this->year);

    $this->actingAs($this->admin)
        ->post(route('users.passwords.reset'), [
            'type' => 'siswa',
            'scope' => 'class',
            'class_id' => $this->classA->id,
        ]);

    $response = $this->actingAs($this->admin)
        ->get(route('users.generate.download'))
        ->assertOk();

    $csv = $response->streamedContent();

    expect($response->headers->get('content-disposition'))->toContain('password VII A.csv')
        ->and($csv)->toContain('Nama,Kelas,Email,Password')
        ->and($csv)->toContain('VII A')
        ->and($csv)->not->toContain('Role');
});

it('reports when no account matches the selected scope', function () {
    studentAccount('2425.7.001', 'Siswa Kelas A', $this->classA, $this->year);

    $this->actingAs($this->admin)
        ->post(route('users.passwords.reset'), [
            'type' => 'guru',
            'scope' => 'all',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('error');

    expect(ActivityLog::where('action', 'user.password_reset')->exists())->toBeFalse();
});

it('resets a single user password from the edit page', function () {
    $target = teacherAccount('198501012010011002', 'Guru Dua');

    $this->actingAs($this->admin)
        ->post(route('users.password.reset', $target))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('generated_accounts', fn ($accounts) => count($accounts) === 1
            && $accounts[0]['email'] === $target->email);

    $password = session('generated_accounts')[0]['password'];

    expect(Hash::check($password, $target->fresh()->password))->toBeTrue()
        ->and(ActivityLog::where('action', 'user.password_reset')->where('subject_id', $target->id)->exists())->toBeTrue();
});

it('refuses to reset the password of the current admin account', function () {
    $this->actingAs($this->admin)
        ->from(route('users.edit', $this->admin))
        ->post(route('users.password.reset', $this->admin))
        ->assertRedirect(route('users.edit', $this->admin))
        ->assertSessionHas('error');

    expect(Hash::check('password', $this->admin->fresh()->password))->toBeTrue();
});

it('blocks non-management roles from resetting passwords', function () {
    $guru = teacherAccount('198501012010011003', 'Guru Tiga');
    $target = User::factory()->create();
    $target->assignRole('siswa');

    $this->actingAs($guru)
        ->post(route('users.password.reset', $target))
        ->assertForbidden();

    $this->actingAs($guru)
        ->post(route('users.passwords.reset'), ['type' => 'siswa', 'scope' => 'all'])
        ->assertForbidden();
});
