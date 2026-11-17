<?php

use App\Models\AcademicYear;
use App\Models\PointLedger;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);
});

test('student dashboard shows the stored avatar and WIB timestamps', function () {
    $user = User::factory()->create([
        'name' => 'Siswa Contoh',
        'avatar_path' => 'avatars/siswa-contoh.png',
    ]);
    $user->assignRole('siswa');

    $student = Student::create([
        'user_id' => $user->id,
        'full_name' => 'Siswa Contoh',
        'nis' => 'NIS-100',
        'nisn' => '0010000000',
        'gender' => 'L',
        'status' => 'active',
    ]);

    $academicYear = AcademicYear::create([
        'name' => '2026/2027',
        'start_date' => now(),
        'is_active' => true,
        'status' => 'active',
    ]);

    $ledger = PointLedger::create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'direction' => PointLedger::DIR_DEBIT,
        'amount' => 100,
        'balance_after' => 1900,
        'transaction_type' => PointLedger::TYPE_VIOLATION,
        'reason' => 'Catatan kedisiplinan',
    ]);
    $ledger->created_at = Carbon::parse('2026-08-14 21:37:00', 'UTC');
    $ledger->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewIs('dashboard-siswa')
        ->assertSee('alt="Foto profil Siswa Contoh"', false)
        ->assertSee('src="'.asset('storage/'.$user->avatar_path).'"', false)
        ->assertSee('15 Agt 2026, 04:37 WIB');
});

test('student profile page is read-only', function () {
    $user = User::factory()->create([
        'name' => 'Siswa Contoh',
        'email' => 'siswa@example.com',
        'nip' => 'NIP-123',
        'avatar_path' => null,
    ]);
    $user->assignRole('siswa');

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Profil Saya')
        ->assertSee(asset('assets/images/user/default-avatar.png'), false)
        ->assertSee('id="name" name="name" type="text" class="field-input cursor-not-allowed bg-slate-100 dark:bg-zinc-800 text-slate-500"', false)
        ->assertSee('id="email" name="email" type="email" class="field-input cursor-not-allowed bg-slate-100 dark:bg-zinc-800 text-slate-500"', false)
        ->assertSee('disabled autocomplete="name"', false)
        ->assertSee('disabled autocomplete="username"', false)
        ->assertDontSee('Nomor Induk Pegawai (NIP)')
        ->assertDontSee('NIP:')
        ->assertDontSee('id="avatar"', false)
        ->assertDontSee('name="remove_avatar"', false)
        ->assertDontSee('Simpan Profil')
        ->assertDontSee('Ubah Kata Sandi')
        ->assertDontSee('Zona Bahaya');
});

test('student cannot upload or remove a profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->create(['avatar_path' => 'avatars/profile.png']);
    $user->assignRole('siswa');
    Storage::disk('public')->put('avatars/profile.png', 'image');

    $avatar = UploadedFile::fake()->createWithContent(
        'avatar.png',
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lwoAAAAASUVORK5CYII=')
    );

    $this->actingAs($user)
        ->patch(route('profile.update'), ['avatar' => $avatar])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['remove_avatar' => '1'])
        ->assertForbidden();

    expect($user->fresh()->avatar_path)->toBe('avatars/profile.png');
    Storage::disk('public')->assertExists('avatars/profile.png');
});

test('student cannot change name email or employee number through profile update', function () {
    $user = User::factory()->create([
        'name' => 'Siswa Tetap',
        'email' => 'siswa@example.com',
        'nip' => 'NIP-123',
    ]);
    $user->assignRole('siswa');

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nama Diubah',
            'email' => 'diubah@example.com',
            'nip' => 'NIP-999',
        ])
        ->assertForbidden();

    expect($user->fresh()->name)->toBe('Siswa Tetap');
    expect($user->fresh()->email)->toBe('siswa@example.com');
    expect($user->fresh()->nip)->toBe('NIP-123');
});

test('student cannot change their password', function () {
    $user = User::factory()->create();
    $user->assignRole('siswa');

    $this->actingAs($user)
        ->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertForbidden();

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('student cannot request account deletion', function () {
    $user = User::factory()->create();
    $user->assignRole('siswa');

    $this->actingAs($user)
        ->delete(route('profile.destroy'), [
            'confirm_name' => $user->name,
            'password' => 'password',
        ])
        ->assertForbidden();

    expect($user->fresh()->deletion_requested_at)->toBeNull();
});

test('staff can upload a profile photo larger than two megabytes', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $avatar = UploadedFile::fake()->createWithContent(
        'avatar.png',
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lwoAAAAASUVORK5CYII=')
        .str_repeat("\0", 3 * 1024 * 1024)
    );

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $avatar,
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasNoErrors();

    $stored = $user->fresh()->avatar_path;
    expect($stored)->toStartWith('avatars/');
    Storage::disk('public')->assertExists($stored);
});

test('staff cannot upload a profile photo above fifty megabytes', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->create('avatar.png', 51201),
        ])
        ->assertSessionHasErrors('avatar');

    expect($user->fresh()->avatar_path)->toBeNull();
});

test('users without a profile photo use the built-in default avatar', function () {
    $user = User::factory()->create(['avatar_path' => null]);

    expect($user->avatar_url)->toBe(asset('assets/images/user/default-avatar.png'));
});
