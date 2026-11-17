<?php

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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

it('sorts the user list by account status', function () {
    $inactive = User::factory()->create(['name' => 'Akun Nonaktif Sortir', 'is_active' => false]);
    $inactive->assignRole('siswa');

    $active = User::factory()->create(['name' => 'Akun Aktif Sortir', 'is_active' => true]);
    $active->assignRole('siswa');

    $this->actingAs($this->admin)
        ->get(route('users.index', ['sort' => 'is_active', 'order' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder(['Akun Nonaktif Sortir', 'Akun Aktif Sortir']);

    $this->actingAs($this->admin)
        ->get(route('users.index', ['sort' => 'is_active', 'order' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder(['Akun Aktif Sortir', 'Akun Nonaktif Sortir']);
});

it('sorts the user list by role', function () {
    $bk = User::factory()->create(['name' => 'BK Role Sortir']);
    $bk->assignRole('bk');

    $guru = User::factory()->create(['name' => 'Guru Role Sortir']);
    $guru->assignRole('guru');

    $this->actingAs($this->admin)
        ->get(route('users.index', ['sort' => 'role', 'order' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder(['BK Role Sortir', 'Guru Role Sortir']);

    $this->actingAs($this->admin)
        ->get(route('users.index', ['sort' => 'role', 'order' => 'desc']))
        ->assertOk()
        ->assertSeeInOrder(['Guru Role Sortir', 'BK Role Sortir']);
});

it('keeps the role sort when the list is filtered to teachers', function () {
    $bk = User::factory()->create(['name' => 'BK Guru Sortir']);
    $bk->assignRole('bk');

    $guru = User::factory()->create(['name' => 'Guru Biasa Sortir']);
    $guru->assignRole('guru');

    $siswa = User::factory()->create(['name' => 'Siswa Tidak Ikut']);
    $siswa->assignRole('siswa');

    $this->actingAs($this->admin)
        ->get(route('users.index', ['type' => 'guru', 'sort' => 'role', 'order' => 'asc']))
        ->assertOk()
        ->assertSeeInOrder(['BK Guru Sortir', 'Guru Biasa Sortir'])
        ->assertDontSee('Siswa Tidak Ikut');
});

it('follows the teacher filter in the bulk password reset form', function () {
    $this->actingAs($this->admin)
        ->get(route('users.index', ['type' => 'guru']))
        ->assertOk()
        ->assertSee('value="guru" selected', false);
});

it('shows the profile photo on the user list and detail page', function () {
    $user = User::factory()->create(['name' => 'Siswa Berfoto Daftar']);
    $user->assignRole('siswa');

    $student = Student::create([
        'user_id' => $user->id,
        'nisn' => '0010000042',
        'nis' => '2425.7.042',
        'full_name' => 'ZIDAN SAYYID NUGRAHA',
        'gender' => 'L',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee($user->avatar_url)
        ->assertSee(route('students.show', $student))
        ->assertDontSee('&middot;', false)
        ->assertDontSee('Permintaan Hapus');

    $this->actingAs($this->admin)
        ->get(route('users.show', $user))
        ->assertOk()
        ->assertSee($user->avatar_url)
        ->assertSee(route('students.show', $student));
});
