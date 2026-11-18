<?php

use App\Models\AchievementCategory;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('only lists achievement categories that are currently valid', function () {
    $parent = AchievementCategory::create([
        'code' => 'A-ROOT',
        'name' => 'Prestasi Akademik',
        'points' => 0,
        'status' => 'active',
    ]);

    AchievementCategory::create([
        'code' => 'A-VALID',
        'parent_id' => $parent->id,
        'name' => 'Kategori Sedang Berlaku',
        'points' => 10,
        'status' => 'active',
        'effective_date' => today()->subDay(),
        'expiry_date' => today()->addDay(),
    ]);

    AchievementCategory::create([
        'code' => 'A-EXPIRED',
        'parent_id' => $parent->id,
        'name' => 'Kategori Kedaluwarsa',
        'points' => 10,
        'status' => 'active',
        'expiry_date' => today()->subDay(),
    ]);

    AchievementCategory::create([
        'code' => 'A-FUTURE',
        'parent_id' => $parent->id,
        'name' => 'Kategori Belum Berlaku',
        'points' => 10,
        'status' => 'active',
        'effective_date' => today()->addDay(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('achievement-records.create'))
        ->assertOk()
        ->assertSee('A-VALID')
        ->assertDontSee('A-EXPIRED')
        ->assertDontSee('A-FUTURE');
});
