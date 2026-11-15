<?php

use App\Models\Student;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['admin', 'siswa'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    }
});

it('shows pending account deletion requests in the user management list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $pendingUser = User::factory()->create([
        'deletion_requested_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('users.index', ['deletion_requested' => '1']))
        ->assertOk()
        ->assertSee($pendingUser->name)
        ->assertSee('Permintaan hapus')
        ->assertSee(route('users.deletion-requests.approve', $pendingUser), false)
        ->assertSee(route('users.deletion-requests.reject', $pendingUser), false);
});

it('deactivates an account and retains its student link when an administrator approves deletion', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $pendingUser = User::factory()->create([
        'deletion_requested_at' => now(),
    ]);
    $pendingUser->createToken('mobile');
    $student = Student::create([
        'user_id' => $pendingUser->id,
        'full_name' => 'Siswa Menunggu',
        'nis' => '120000000001',
        'nisn' => '1200000001',
        'gender' => 'L',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->patch(route('users.deletion-requests.approve', $pendingUser))
        ->assertRedirect(route('users.index'))
        ->assertSessionHasNoErrors();

    expect($pendingUser->fresh()->is_active)->toBeFalse();
    expect($pendingUser->fresh()->trashed())->toBeFalse();
    expect($student->fresh()->user_id)->toBe($pendingUser->id);
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $pendingUser->id,
        'name' => 'mobile',
    ]);
});

it('clears a deletion request when an administrator rejects it', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $pendingUser = User::factory()->create([
        'deletion_requested_at' => now(),
    ]);

    $this->actingAs($admin)
        ->patch(route('users.deletion-requests.reject', $pendingUser))
        ->assertRedirect(route('users.index'))
        ->assertSessionHasNoErrors();

    expect($pendingUser->fresh()->deletion_requested_at)->toBeNull();
    expect($pendingUser->fresh()->trashed())->toBeFalse();
});

it('requires another administrator to approve their own account deletion request', function () {
    $admin = User::factory()->create([
        'deletion_requested_at' => now(),
    ]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->patch(route('users.deletion-requests.approve', $admin))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('error', 'Permintaan akun sendiri harus diproses oleh pengelola lain.');

    expect($admin->fresh()->is_active)->toBeTrue();
    expect($admin->fresh()->deletion_requested_at)->not->toBeNull();
});

it('forbids students from processing account deletion requests', function () {
    $studentUser = User::factory()->create();
    $studentUser->assignRole('siswa');
    $pendingUser = User::factory()->create([
        'deletion_requested_at' => now(),
    ]);

    $this->actingAs($studentUser)
        ->patch(route('users.deletion-requests.approve', $pendingUser))
        ->assertForbidden();

    expect($pendingUser->fresh()->trashed())->toBeFalse();
});

it('deactivates an account directly without deleting its student identity link', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $user = User::factory()->create();
    $student = Student::create([
        'user_id' => $user->id,
        'full_name' => 'Siswa Tetap',
        'nis' => '120000000002',
        'nisn' => '1200000002',
        'gender' => 'L',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'));

    expect($user->fresh()->is_active)->toBeFalse();
    expect($user->fresh()->trashed())->toBeFalse();
    expect($student->fresh()->user_id)->toBe($user->id);
});

it('allows a manager to reactivate a non-deleted account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $user = User::factory()->create([
        'is_active' => false,
        'deletion_approved_at' => now(),
        'deletion_approved_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('users.activate', $user))
        ->assertRedirect(route('users.index'));

    expect($user->fresh()->is_active)->toBeTrue();
    expect($user->fresh()->deletion_approved_at)->toBeNull();
    expect($user->fresh()->deletion_approved_by)->toBeNull();
});
