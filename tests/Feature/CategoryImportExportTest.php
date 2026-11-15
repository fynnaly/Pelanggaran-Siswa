<?php

use App\Models\AchievementCategory;
use App\Models\User;
use App\Models\ViolationCategory;
use App\Support\Xlsx;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['admin', 'bk', 'kesiswaan', 'kepala_sekolah', 'guru'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $this->manager = User::factory()->create();
    $this->manager->assignRole('admin');
    $this->actingAs($this->manager);
});

it('provides XLSX templates and readable exports for both category types', function () {
    $violationParent = ViolationCategory::create([
        'code' => 'V-ROOT',
        'name' => 'Kedisiplinan',
        'severity' => 'ringan',
        'points' => 0,
        'status' => 'active',
    ]);

    ViolationCategory::create([
        'parent_id' => $violationParent->id,
        'code' => 'V-001',
        'name' => 'Terlambat',
        'severity' => 'ringan',
        'points' => 8,
        'status' => 'active',
    ]);

    $achievementParent = AchievementCategory::create([
        'code' => 'A-ROOT',
        'name' => 'Kedisiplinan',
        'points' => 0,
        'status' => 'active',
    ]);

    AchievementCategory::create([
        'parent_id' => $achievementParent->id,
        'code' => 'A-001',
        'name' => 'Hadir tepat waktu',
        'points' => 10,
        'status' => 'active',
    ]);

    foreach ([
        ['violation-categories.template', 'violation-categories.export', 'V-001'],
        ['achievement-categories.template', 'achievement-categories.export', 'A-001'],
    ] as [$templateRoute, $exportRoute, $code]) {
        $this->get(route($templateRoute))->assertOk();

        $response = $this->get(route($exportRoute))->assertOk();
        $filePath = $response->baseResponse->getFile()->getPathname();
        $rows = Xlsx::read($filePath);

        expect($rows[0])->toContain('parent_code')
            ->and(collect($rows)->flatten()->contains($code))->toBeTrue();
    }
});

it('imports violation categories by parent code, skips duplicates, and reports invalid rows', function () {
    ViolationCategory::create([
        'code' => 'V-EXIST',
        'name' => 'Nama Lama',
        'severity' => 'ringan',
        'points' => 4,
        'status' => 'active',
    ]);

    $csv = implode("\n", [
        'code,parent_code,name,severity,points,description,sanksi,group_type,is_zero_tolerance,status',
        'V-NEW,,Kedisiplinan,ringan,0,,,kedisiplinan,0,active',
        'V-NEW-1,V-NEW,Terlambat,ringan,8,,,kedisiplinan,0,active',
        'V-EXIST,,Nama Baru,ringan,12,,,kedisiplinan,0,draft',
        'V-BAD,,Poin Salah,berat,bukan-angka,,,kedisiplinan,0,active',
    ]);

    $this->from(route('violation-categories.index'))->post(route('violation-categories.import'), [
        'file' => UploadedFile::fake()->createWithContent('categories.csv', $csv),
    ])->assertRedirect(route('violation-categories.index'))
        ->assertSessionHas('success')
        ->assertSessionHas('success', fn (string $message) => str_contains($message, '2 kategori ditambahkan')
            && str_contains($message, '2 dilewati')
            && str_contains($message, 'Baris 5'));

    $parent = ViolationCategory::where('code', 'V-NEW')->firstOrFail();
    $child = ViolationCategory::where('code', 'V-NEW-1')->firstOrFail();

    expect($child->parent_id)->toBe($parent->id)
        ->and($child->points)->toBe(8)
        ->and(ViolationCategory::where('code', 'V-EXIST')->value('name'))->toBe('Nama Lama')
        ->and(ViolationCategory::where('code', 'V-BAD')->exists())->toBeFalse();
});

it('imports achievement categories from XLSX and does not overwrite existing codes', function () {
    AchievementCategory::create([
        'code' => 'A-EXIST',
        'name' => 'Nama Lama',
        'points' => 5,
        'status' => 'active',
    ]);

    $path = Xlsx::write([
        ['code', 'parent_code', 'name', 'points', 'description', 'group_type', 'max_points_per_event', 'effective_date', 'expiry_date', 'status'],
        ['A-ROOT', '', 'Akademik', '0', '', 'akademik', '', '', '', 'active'],
        ['A-NEW', 'A-ROOT', 'Juara kelas', '15', '', 'akademik', '', '', '', 'active'],
        ['A-EXIST', '', 'Nama Baru', '20', '', 'akademik', '', '', '', 'draft'],
    ], 'achievement-categories');

    try {
        $file = new UploadedFile(
            $path,
            'achievement-categories.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $this->from(route('achievement-categories.index'))->post(route('achievement-categories.import'), ['file' => $file])
            ->assertRedirect(route('achievement-categories.index'))
            ->assertSessionHas('success', fn (string $message) => str_contains($message, '2 kategori ditambahkan')
                && str_contains($message, '1 dilewati'));
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }

    $parent = AchievementCategory::where('code', 'A-ROOT')->firstOrFail();
    $child = AchievementCategory::where('code', 'A-NEW')->firstOrFail();

    expect($child->parent_id)->toBe($parent->id)
        ->and($child->points)->toBe(15)
        ->and(AchievementCategory::where('code', 'A-EXIST')->value('name'))->toBe('Nama Lama');
});

it('rejects category imports with missing required headers', function () {
    $this->from(route('achievement-categories.index'))->post(route('achievement-categories.import'), [
        'file' => UploadedFile::fake()->createWithContent('categories.csv', "code,name\nA-1,Prestasi\n"),
    ])->assertRedirect(route('achievement-categories.index'))
        ->assertSessionHas('error');

    expect(AchievementCategory::where('code', 'A-1')->exists())->toBeFalse();
});

it('limits category import and export management to authorized roles', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('guru');

    $this->actingAs($teacher)
        ->get(route('violation-categories.export'))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->post(route('achievement-categories.import'), [
            'file' => UploadedFile::fake()->createWithContent('categories.csv', "code,name,points,status\nA-1,Prestasi,5,active\n"),
        ])
        ->assertForbidden();
});
