<?php

use App\Models\Department;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['admin', 'guru', 'wali_kelas', 'bk', 'siswa', 'kesiswaan', 'kepala_sekolah'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
});

// Kode RPL/TKJ/AKT sudah di-seed oleh migration create_departments_table — pakai firstOrCreate.
it('renders the department list for admin', function () {
    Department::firstOrCreate(['code' => 'RPL'], ['name' => 'Rekayasa Perangkat Lunak', 'status' => 'active']);

    $this->actingAs($this->admin)
        ->get(route('jurusan.index'))
        ->assertOk()
        ->assertSee('RPL')
        ->assertSee('Rekayasa Perangkat Lunak');
});

it('renders create, edit and show pages for admin', function () {
    $dept = Department::firstOrCreate(['code' => 'TKJ'], ['name' => 'Teknik Komputer dan Jaringan', 'status' => 'active']);

    $this->actingAs($this->admin)->get(route('jurusan.create'))->assertOk();

    // Bukti binding route: tanpa ->parameters(), view merender model kosong tanpa error.
    $this->actingAs($this->admin)->get(route('jurusan.edit', $dept))->assertOk()->assertSee('TKJ');
    $this->actingAs($this->admin)->get(route('jurusan.show', $dept))->assertOk()->assertSee('Teknik Komputer dan Jaringan');
});

// 'AKT' sudah di-seed migration — pakai kode baru agar lolos validasi unique.
it('stores a department', function () {
    $this->actingAs($this->admin)
        ->post(route('jurusan.store'), ['code' => 'MM', 'name' => 'Multimedia', 'status' => 'active'])
        ->assertRedirect(route('jurusan.index'));

    $this->assertDatabaseHas('departments', ['code' => 'MM']);
});

it('toggles department status', function () {
    $dept = Department::firstOrCreate(['code' => 'RPL'], ['name' => 'Rekayasa Perangkat Lunak', 'status' => 'active']);

    $this->actingAs($this->admin)
        ->patch(route('jurusan.toggle-status', $dept))
        ->assertRedirect();

    $this->assertSame('inactive', $dept->fresh()->status);
});

it('blocks students from the department pages', function () {
    $siswa = User::factory()->create();
    $siswa->assignRole('siswa');

    $this->actingAs($siswa)->get(route('jurusan.index'))->assertForbidden();
    $this->actingAs($siswa)->post(route('jurusan.store'), ['code' => 'X', 'name' => 'X'])->assertForbidden();
});

it('blocks users with no role at all', function () {
    $nobody = User::factory()->create(); // hasil registrasi publik lama — tanpa role

    $this->actingAs($nobody)->get(route('jurusan.index'))->assertForbidden();
});

it('lets guru view but not mutate', function () {
    $guru = User::factory()->create();
    $guru->assignRole('guru');
    $dept = Department::firstOrCreate(['code' => 'RPL'], ['name' => 'Rekayasa Perangkat Lunak', 'status' => 'active']);

    $this->actingAs($guru)->get(route('jurusan.index'))->assertOk();
    $this->actingAs($guru)->get(route('jurusan.create'))->assertForbidden();
    $this->actingAs($guru)->patch(route('jurusan.toggle-status', $dept))->assertForbidden();
});
