<?php

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['admin', 'guru', 'bk', 'siswa', 'kesiswaan', 'kepala_sekolah'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $this->withoutMiddleware(PreventRequestForgery::class);
    Storage::fake('public');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

function editAvatarUpload(string $name = 'foto.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lwoAAAAASUVORK5CYII=')
    );
}

it('shows profile fields on the single user edit page', function () {
    $user = User::factory()->create(['name' => 'Guru Profil']);
    $user->assignRole('guru');

    $this->actingAs($this->admin)
        ->get(route('users.edit', $user))
        ->assertOk()
        ->assertSee('Edit: Guru Profil')
        ->assertSee('name="avatar"', false)
        ->assertSee('name="nip"', false);
});

it('redirects the old profile url to the edit page', function () {
    $user = User::factory()->create();
    $user->assignRole('guru');

    $this->actingAs($this->admin)
        ->get("/users/{$user->id}/profile")
        ->assertRedirect(route('users.edit', $user));
});

it('updates profile data and syncs the avatar to the linked teacher record', function () {
    $user = User::factory()->create([
        'name' => 'Nama Lama',
        'email' => 'lama@example.com',
        'nip' => null,
    ]);
    $user->assignRole('guru');

    $teacher = Teacher::create([
        'user_id' => $user->id,
        'nip' => '198501012010011009',
        'full_name' => 'Guru Tertaut',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->put(route('users.update', $user), [
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
            'nip' => '198501012010011001',
            'avatar' => editAvatarUpload(),
        ])
        ->assertRedirect(route('users.edit', $user))
        ->assertSessionHasNoErrors();

    $path = $user->fresh()->avatar_path;

    expect($user->fresh()->name)->toBe('Nama Baru')
        ->and($user->fresh()->email)->toBe('baru@example.com')
        ->and($user->fresh()->nip)->toBe('198501012010011001')
        ->and($path)->toStartWith('avatars/')
        ->and($teacher->fresh()->avatar_path)->toBe($path)
        ->and($teacher->fresh()->user_id)->toBe($user->id);

    Storage::disk('public')->assertExists($path);
});

it('syncs the avatar to the linked student record', function () {
    $user = User::factory()->create();
    $user->assignRole('siswa');

    $student = Student::create([
        'user_id' => $user->id,
        'nisn' => '0010000009',
        'nis' => '202412010009',
        'full_name' => 'Siswa Tertaut',
        'gender' => 'L',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => editAvatarUpload(),
        ])
        ->assertRedirect(route('users.edit', $user))
        ->assertSessionHasNoErrors();

    expect($student->fresh()->avatar_path)->toBe($user->fresh()->avatar_path)
        ->and($student->fresh()->user_id)->toBe($user->id);
});

it('removes the profile avatar on request', function () {
    $user = User::factory()->create(['avatar_path' => 'avatars/lama.png']);
    $user->assignRole('guru');
    Storage::disk('public')->put('avatars/lama.png', 'image');

    $this->actingAs($this->admin)
        ->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'remove_avatar' => '1',
        ])
        ->assertRedirect(route('users.edit', $user));

    expect($user->fresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/lama.png');
});

it('refuses profile editing for non-management roles', function () {
    $guru = User::factory()->create();
    $guru->assignRole('guru');

    $target = User::factory()->create();
    $target->assignRole('siswa');

    $this->actingAs($guru)
        ->get(route('users.edit', $target))
        ->assertForbidden();

    $this->actingAs($guru)
        ->put(route('users.update', $target), [
            'name' => 'Diubah',
            'email' => 'diubah@example.com',
        ])
        ->assertForbidden();

    expect($target->fresh()->name)->not->toBe('Diubah');
});

function editLargeAvatarUpload(int $megabytes, string $name = 'foto-besar.png'): UploadedFile
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lwoAAAAASUVORK5CYII=');

    return UploadedFile::fake()->createWithContent($name, $png.str_repeat("\0", $megabytes * 1024 * 1024));
}

it('accepts a user profile photo larger than the old 5MB limit', function () {
    $user = User::factory()->create();
    $user->assignRole('guru');

    $this->actingAs($this->admin)
        ->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => editLargeAvatarUpload(6),
        ])
        ->assertRedirect(route('users.edit', $user))
        ->assertSessionHasNoErrors();

    expect($user->fresh()->avatar_path)->toStartWith('avatars/');
});

it('rejects a user profile photo above 15MB', function () {
    $user = User::factory()->create();
    $user->assignRole('guru');

    $this->actingAs($this->admin)
        ->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => editLargeAvatarUpload(16),
        ])
        ->assertSessionHasErrors(['avatar' => 'Ukuran foto profil maksimal 15MB.']);

    expect($user->fresh()->avatar_path)->toBeNull();
});
