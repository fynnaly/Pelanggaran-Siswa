<?php

use App\Models\AcademicYear;
use App\Models\PointLedger;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

test('student photo upload appears on the dashboard with Indonesian WIB timestamps', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

    $user = User::factory()->create([
        'name' => 'Siswa Contoh',
        'nip' => 'NIP-123',
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

    Storage::fake('public');
    $avatar = UploadedFile::fake()->createWithContent(
        'avatar.png',
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lwoAAAAASUVORK5CYII=')
    );

    $this->actingAs($user)
        ->patch(route('profile.update'), ['avatar' => $avatar])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->avatar_path)->toStartWith('avatars/');
    Storage::disk('public')->assertExists($user->avatar_path);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertViewIs('dashboard-siswa')
        ->assertSee('alt="Foto profil Siswa Contoh"', false)
        ->assertSee('src="'.asset('storage/'.$user->avatar_path).'"', false)
        ->assertSee('15 Agt 2026, 04:37 WIB');
});

test('student profile locks identity fields and hides employee number', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

    $user = User::factory()->create([
        'name' => 'Siswa Contoh',
        'email' => 'siswa@example.com',
        'nip' => 'NIP-123',
        'avatar_path' => 'avatars/profile.png',
    ]);
    $user->assignRole('siswa');

    Storage::fake('public');

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('id="name" name="name" type="text" class="field-input cursor-not-allowed bg-slate-100 dark:bg-zinc-800 text-slate-500"', false)
        ->assertSee('id="email" name="email" type="email" class="field-input cursor-not-allowed bg-slate-100 dark:bg-zinc-800 text-slate-500"', false)
        ->assertSee('name="name" type="text"', false)
        ->assertSee('name="email" type="email"', false)
        ->assertSee('disabled autocomplete="name"', false)
        ->assertSee('disabled autocomplete="username"', false)
        ->assertDontSee('Nomor Induk Pegawai (NIP)')
        ->assertDontSee('NIP:')
        ->assertSee('data-lucide="trash-2"', false)
        ->assertSee('type="submit" name="remove_avatar" value="1"', false);
});

test('student cannot change name email or employee number through profile update', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

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
        ->assertInvalid(['name', 'email', 'nip']);

    expect($user->fresh()->name)->toBe('Siswa Tetap');
    expect($user->fresh()->email)->toBe('siswa@example.com');
    expect($user->fresh()->nip)->toBe('NIP-123');
});

test('student can remove an uploaded profile photo', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

    $user = User::factory()->create(['avatar_path' => 'avatars/profile.png']);
    $user->assignRole('siswa');
    Storage::fake('public');
    Storage::disk('public')->put('avatars/profile.png', 'image');

    $this->actingAs($user)
        ->patch(route('profile.update'), ['remove_avatar' => '1'])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasNoErrors();

    expect($user->fresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/profile.png');
});
