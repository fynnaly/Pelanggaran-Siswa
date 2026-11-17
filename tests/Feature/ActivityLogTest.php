<?php

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
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

function uploadCsv(string $name, string $csv): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $csv);
}

it('lets admin and kesiswaan read the activity log but not other roles', function () {
    $this->actingAs($this->admin);
    ActivityLog::record('user.generate', '3 akun siswa berhasil dibuat otomatis', ['total' => 3]);

    $this->actingAs($this->admin)->get(route('logs.index'))->assertOk()->assertSee('Log Aktivitas');

    $kesiswaan = User::factory()->create();
    $kesiswaan->assignRole('kesiswaan');
    $this->actingAs($kesiswaan)->get(route('logs.index'))->assertOk();

    foreach (['kepala_sekolah', 'guru'] as $role) {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user)->get(route('logs.index'))->assertForbidden();
        $this->actingAs($user)->get(route('logs.show', ActivityLog::firstOrFail()))->assertForbidden();
    }
});

it('records a user import with every skipped row and without any password', function () {
    $csv = implode("\n", [
        'Nama,Email,Password,Role',
        'Siswa Masuk,satu@example.com,Rahasia123!,siswa',
        'Siswa Gagal,satu@example.com,LainLagi456!,siswa',
        'Role Salah,tiga@example.com,Password789!,tidak_ada',
    ]);

    $this->actingAs($this->admin)
        ->from(route('users.index'))
        ->post(route('users.import'), ['file' => uploadCsv('pengguna.csv', $csv)])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success', fn (string $message) => str_contains($message, '1 pengguna ditambahkan')
            && str_contains($message, '2 dilewati'));

    $log = ActivityLog::where('action', 'user.import')->firstOrFail();
    $raw = json_encode($log->properties);

    expect($log->properties['status'])->toBe('ok')
        ->and($log->properties['file'])->toBe('pengguna.csv')
        ->and($log->properties['imported'])->toBe(1)
        ->and($log->properties['skipped'])->toBe(2)
        ->and($log->properties['issues_total'])->toBe(2)
        ->and($log->properties['issues'][0])->toContain('Baris 3')
        ->and($log->properties['issues'][1])->toContain('Baris 4')
        ->and($log->properties['accounts'])->toHaveCount(1)
        ->and($log->properties['accounts'][0]['email'])->toBe('satu@example.com')
        ->and($raw)->not->toContain('Rahasia123!')
        ->and($raw)->not->toContain('LainLagi456!')
        ->and($raw)->not->toContain('Password789!');

    $this->actingAs($this->admin)
        ->get(route('logs.show', $log))
        ->assertOk()
        ->assertSee('Baris 3')
        ->assertSee('Baris 4')
        ->assertSee('Password tidak pernah dicatat di log')
        ->assertDontSee('Rahasia123!');
});

it('records failed user imports with a readable reason', function () {
    $csv = implode("\n", [
        'Foo,Bar',
        'a,b',
    ]);

    $this->actingAs($this->admin)
        ->from(route('users.index'))
        ->post(route('users.import'), ['file' => uploadCsv('salah-header.csv', $csv)])
        ->assertSessionHas('error');

    $log = ActivityLog::where('action', 'user.import')->firstOrFail();

    expect($log->properties['status'])->toBe('failed')
        ->and($log->properties['file'])->toBe('salah-header.csv')
        ->and($log->properties['reason'])->toContain('Header tidak valid');
});

it('records a student import with created students, new classes, and skipped rows', function () {
    AcademicYear::create([
        'name' => '2026/2027',
        'start_date' => now()->startOfYear(),
        'is_active' => true,
        'status' => 'active',
    ]);

    $csv = implode("\n", [
        'NISN,NIS,Nama Lengkap,Kelas (nama)',
        '0010000001,2425.7.001,Siswa Masuk,VII A',
        '123,2425.7.002,Siswa NISN Salah,VII A',
    ]);

    $this->actingAs($this->admin)
        ->from(route('students.index'))
        ->post(route('students.import'), ['file' => uploadCsv('siswa.csv', $csv)])
        ->assertSessionHas('success', fn (string $message) => str_contains($message, '1 siswa ditambahkan')
            && str_contains($message, '1 dilewati'));

    $log = ActivityLog::where('action', 'student.import')->firstOrFail();

    expect($log->properties['imported'])->toBe(1)
        ->and($log->properties['skipped'])->toBe(1)
        ->and($log->properties['classes_created'])->toBe(1)
        ->and($log->properties['classes'])->toContain('VII A')
        ->and($log->properties['accounts'][0]['identity'])->toBe('2425.7.001')
        ->and($log->properties['accounts'][0]['class'])->toBe('VII A')
        ->and($log->properties['issues'][0])->toContain('Baris 3')
        ->and(Student::where('nis', '2425.7.001')->exists())->toBeTrue()
        ->and(SchoolClass::where('name', 'VII A')->exists())->toBeTrue();
});

it('records a teacher import with its own action label', function () {
    $csv = implode("\n", [
        'Nama Lengkap,NIP,Mata Pelajaran',
        'Guru Satu,198501012010011001,Matematika',
        'Guru Dua,198501012010011001,Fisika',
    ]);

    $this->actingAs($this->admin)
        ->from(route('teachers.index'))
        ->post(route('teachers.import'), ['file' => uploadCsv('guru.csv', $csv)])
        ->assertSessionHas('success', fn (string $message) => str_contains($message, '1 guru ditambahkan')
            && str_contains($message, '1 dilewati'));

    $log = ActivityLog::where('action', 'teacher.import')->firstOrFail();

    expect($log->properties['imported'])->toBe(1)
        ->and($log->properties['skipped'])->toBe(1)
        ->and($log->properties['issues'][0])->toContain('sudah terdaftar')
        ->and($log->label())->toBe('Import guru');
});
