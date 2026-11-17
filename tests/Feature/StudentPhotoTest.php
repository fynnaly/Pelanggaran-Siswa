<?php

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['admin', 'guru', 'wali_kelas', 'bk', 'siswa', 'kesiswaan', 'kepala_sekolah'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $this->withoutMiddleware(PreventRequestForgery::class);
    Storage::fake('public');

    $this->student = Student::create([
        'nisn' => '0010000001',
        'nis' => '202412010001',
        'full_name' => 'Siswa Berfoto',
        'gender' => 'L',
        'status' => 'active',
    ]);
});

function pngUpload(string $name = 'foto.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lwoAAAAASUVORK5CYII=')
    );
}

it('lets bk replace a student photo and syncs it to the linked account', function () {
    $bk = User::factory()->create();
    $bk->assignRole('bk');

    $account = User::factory()->create(['avatar_path' => null]);
    $account->assignRole('siswa');
    $this->student->update(['user_id' => $account->id]);

    $this->actingAs($bk)
        ->put(route('students.update', $this->student), ['avatar' => pngUpload()])
        ->assertRedirect(route('students.index'))
        ->assertSessionHasNoErrors();

    $path = $this->student->fresh()->avatar_path;

    expect($path)->toStartWith('avatars/')
        ->and($account->fresh()->avatar_path)->toBe($path);

    Storage::disk('public')->assertExists($path);
});

it('lets kesiswaan remove a student photo', function () {
    $kesiswaan = User::factory()->create();
    $kesiswaan->assignRole('kesiswaan');

    $account = User::factory()->create(['avatar_path' => 'avatars/lama.png']);
    $account->assignRole('siswa');
    $this->student->update(['user_id' => $account->id, 'avatar_path' => 'avatars/lama.png']);
    Storage::disk('public')->put('avatars/lama.png', 'image');

    $this->actingAs($kesiswaan)
        ->put(route('students.update', $this->student), ['remove_avatar' => '1'])
        ->assertRedirect(route('students.index'));

    expect($this->student->fresh()->avatar_path)->toBeNull()
        ->and($account->fresh()->avatar_path)->toBeNull();

    Storage::disk('public')->assertMissing('avatars/lama.png');
});

it('lets admin replace a student photo and syncs it to the linked account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $account = User::factory()->create(['avatar_path' => null]);
    $account->assignRole('siswa');
    $this->student->update(['user_id' => $account->id]);

    $this->actingAs($admin)
        ->put(route('students.update', $this->student), ['avatar' => pngUpload()])
        ->assertRedirect(route('students.index'))
        ->assertSessionHasNoErrors();

    $path = $this->student->fresh()->avatar_path;

    expect($path)->toStartWith('avatars/')
        ->and($account->fresh()->avatar_path)->toBe($path);

    Storage::disk('public')->assertExists($path);
});

it('blocks roles outside admin, bk, and kesiswaan from replacing a student photo', function () {
    $kepalaSekolah = User::factory()->create();
    $kepalaSekolah->assignRole('kepala_sekolah');

    $this->actingAs($kepalaSekolah)
        ->put(route('students.update', $this->student), ['avatar' => pngUpload()])
        ->assertForbidden();

    expect($this->student->fresh()->avatar_path)->toBeNull();
});

it('shows the photo upload field to admin, bk, and kesiswaan only', function () {
    foreach (['admin', 'bk', 'kesiswaan'] as $role) {
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('students.edit', $this->student))
            ->assertOk()
            ->assertSee('name="avatar"', false);
    }

    $kepalaSekolah = User::factory()->create();
    $kepalaSekolah->assignRole('kepala_sekolah');

    $this->actingAs($kepalaSekolah)
        ->get(route('students.edit', $this->student))
        ->assertOk()
        ->assertDontSee('name="avatar"', false);
});

function largePhotoUpload(int $megabytes, string $name = 'foto-besar.png'): UploadedFile
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lwoAAAAASUVORK5CYII=');

    return UploadedFile::fake()->createWithContent($name, $png.str_repeat("\0", $megabytes * 1024 * 1024));
}

it('accepts a student photo larger than the old 5MB limit', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('students.update', $this->student), ['avatar' => largePhotoUpload(6)])
        ->assertSessionHasNoErrors();

    expect($this->student->fresh()->avatar_path)->toStartWith('avatars/');
});

it('rejects a student photo above 15MB', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->put(route('students.update', $this->student), ['avatar' => largePhotoUpload(16)])
        ->assertSessionHasErrors(['avatar' => 'Ukuran foto profil maksimal 15MB.']);

    expect($this->student->fresh()->avatar_path)->toBeNull();
});

it('shows the student profile photo on the detail page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->student->update(['avatar_path' => 'avatars/siswa-detail.png']);
    Storage::disk('public')->put('avatars/siswa-detail.png', 'image');

    $this->actingAs($admin)
        ->get(route('students.show', $this->student))
        ->assertOk()
        ->assertSee($this->student->avatar_url);
});
