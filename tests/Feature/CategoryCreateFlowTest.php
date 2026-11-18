<?php

use App\Models\AchievementCategory;
use App\Models\User;
use App\Models\ViolationCategory;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['admin', 'bk', 'kesiswaan', 'kepala_sekolah', 'guru', 'wali_kelas'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->guru = User::factory()->create();
    $this->guru->assignRole('guru');
});

it('shows reference categories and the activity log to admin', function () {
    $this->actingAs($this->admin)
        ->get(route('achievement-categories.index'))
        ->assertOk()
        ->assertSee('Data Referensi')
        ->assertSee('Kategori Pelanggaran')
        ->assertSee('Kategori Prestasi')
        ->assertSee('Log Aktivitas');
});

it('lets admin create an achievement category with the full set of fields', function () {
    $this->actingAs($this->admin)
        ->get(route('achievement-categories.create'))
        ->assertOk()
        ->assertSee('Pengaturan lanjutan')
        ->assertSee('name="group_type"', false)
        ->assertSee('name="max_points_per_event"', false)
        ->assertSee('name="effective_date"', false)
        ->assertSee('name="expiry_date"', false)
        ->assertSee('name="description"', false);

    $this->actingAs($this->admin)
        ->post(route('achievement-categories.store'), [
            'code' => 'R3.1',
            'name' => 'Juara Olimpiade Sains',
            'parent_id' => '',
            'points' => 0,
            'status' => 'active',
            'description' => 'Prestasi akademik tingkat kabupaten.',
            'group_type' => 'akademik',
            'max_points_per_event' => 50,
            'effective_date' => '2026-07-01',
            'expiry_date' => '2027-06-30',
        ])
        ->assertRedirect(route('achievement-categories.index'));

    $category = AchievementCategory::where('code', 'R3.1')->firstOrFail();

    expect($category->description)->toBe('Prestasi akademik tingkat kabupaten.')
        ->and($category->group_type)->toBe('akademik')
        ->and((int) $category->max_points_per_event)->toBe(50)
        ->and($category->effective_date?->format('Y-m-d'))->toBe('2026-07-01')
        ->and($category->expiry_date?->format('Y-m-d'))->toBe('2027-06-30');
});

it('lets admin create a violation category with group and validity dates', function () {
    $this->actingAs($this->admin)
        ->get(route('violation-categories.create'))
        ->assertOk()
        ->assertSee('Pengaturan lanjutan')
        ->assertSee('name="group_type"', false)
        ->assertSee('name="effective_date"', false)
        ->assertSee('name="expiry_date"', false);

    $this->actingAs($this->admin)
        ->post(route('violation-categories.store'), [
            'code' => 'P1.1',
            'name' => 'Terlambat masuk',
            'parent_id' => '',
            'severity' => 'ringan',
            'poin_ke_1' => 8,
            'poin_ke_2' => 16,
            'poin_ke_3' => 32,
            'status' => 'active',
            'description' => 'Terlambat lebih dari 10 menit.',
            'group_type' => 'terlambat',
            'effective_date' => '2026-07-01',
            'expiry_date' => '2027-06-30',
        ])
        ->assertRedirect(route('violation-categories.index'));

    $category = ViolationCategory::where('code', 'P1.1')->firstOrFail();

    expect($category->group_type)->toBe('terlambat')
        ->and((int) $category->points)->toBe(8)
        ->and($category->effective_date?->format('Y-m-d'))->toBe('2026-07-01')
        ->and($category->expiry_date?->format('Y-m-d'))->toBe('2027-06-30');
});

it('hides category management actions from teachers', function () {
    $this->actingAs($this->guru)
        ->get(route('achievement-categories.index'))
        ->assertOk()
        ->assertDontSee(route('achievement-categories.create'), false);

    $this->actingAs($this->guru)
        ->get(route('violation-categories.index'))
        ->assertOk()
        ->assertDontSee(route('violation-categories.create'), false);
});
