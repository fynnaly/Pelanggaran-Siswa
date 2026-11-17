<?php

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['admin', 'guru', 'wali_kelas', 'bk', 'siswa', 'kesiswaan', 'kepala_sekolah'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->withoutMiddleware(PreventRequestForgery::class);
});

it('renders the teacher list for staff', function () {
    $teacher = Teacher::create([
        'full_name' => 'Budi Santoso',
        'gender' => 'L',
        'nip' => '198501012010011001',
        'mata_pelajaran' => 'Matematika',
        'jabatan' => 'Wks. Kesiswaan',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->get(route('teachers.index'))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertSee('Laki-laki')
        ->assertSee('198501012010011001')
        ->assertSee('Matematika')
        ->assertSee('Wks. Kesiswaan');

    expect($teacher->avatar_url)->toContain('default-avatar.png');
});

it('searches teachers by name, nip and subject', function () {
    Teacher::create(['full_name' => 'Siti Aminah', 'nip' => '111', 'mata_pelajaran' => 'Fisika']);
    Teacher::create(['full_name' => 'Andi Pratama', 'nip' => '222', 'mata_pelajaran' => 'Sejarah']);

    $this->actingAs($this->admin)
        ->get(route('teachers.index', ['q' => 'Fisika']))
        ->assertOk()
        ->assertSee('Siti Aminah')
        ->assertDontSee('Andi Pratama');
});

it('stores a teacher linked to a user account', function () {
    $account = User::factory()->create();
    $account->assignRole('guru');

    $this->actingAs($this->admin)
        ->post(route('teachers.store'), [
            'full_name' => 'Rina Wati',
            'nip' => '198701012010011002',
            'mata_pelajaran' => 'Bahasa Indonesia',
            'phone' => '08123456789',
            'status' => 'active',
            'user_id' => $account->id,
        ])
        ->assertRedirect(route('teachers.index'));

    $teacher = Teacher::where('nip', '198701012010011002')->first();

    expect($teacher)->not->toBeNull()
        ->and($teacher->user_id)->toBe($account->id)
        ->and($account->fresh()->teacher?->id)->toBe($teacher->id);
});

it('renders create and edit pages for admin', function () {
    $teacher = Teacher::create([
        'full_name' => 'Rina Wati',
        'nip' => '555',
        'mata_pelajaran' => 'Bahasa Indonesia',
    ]);

    $this->actingAs($this->admin)
        ->get(route('teachers.create'))
        ->assertOk()
        ->assertSee('Tambah Data Guru');

    $this->actingAs($this->admin)
        ->get(route('teachers.edit', $teacher))
        ->assertOk()
        ->assertSee('Rina Wati')
        ->assertSee('Bahasa Indonesia');
});

it('shows teacher options in the user account form', function () {
    Teacher::create(['full_name' => 'Guru Pilihan', 'nip' => '777', 'mata_pelajaran' => 'Kimia']);

    $this->actingAs($this->admin)
        ->get(route('users.create'))
        ->assertOk()
        ->assertSee('Guru Pilihan')
        ->assertSee('Identitas Guru');
});

it('renders user detail and edit pages with teacher identity', function () {
    $account = User::factory()->create();
    $account->assignRole('guru');

    Teacher::create([
        'user_id' => $account->id,
        'full_name' => 'Guru Terhubung',
        'nip' => '888',
        'mata_pelajaran' => 'Biologi',
    ]);

    $this->actingAs($this->admin)
        ->get(route('users.show', $account))
        ->assertOk()
        ->assertSee('Identitas Guru')
        ->assertSee('Biologi');

    $this->actingAs($this->admin)
        ->get(route('users.edit', $account))
        ->assertOk()
        ->assertSee('Identitas Guru');
});

it('toggles teacher status without deleting the record', function () {
    $teacher = Teacher::create(['full_name' => 'Dedi Kurniawan', 'status' => 'active']);

    $this->actingAs($this->admin)
        ->patch(route('teachers.toggle-status', $teacher))
        ->assertRedirect();

    expect($teacher->fresh()->status)->toBe('inactive');
    $this->assertDatabaseHas('teachers', ['id' => $teacher->id]);
});

it('imports teachers from csv and skips duplicate nip', function () {
    Teacher::create(['full_name' => 'Guru Lama', 'nip' => '999']);

    $csv = "NIP,Nama Lengkap,Mata Pelajaran,Telepon,Status\n"
        ."123,Guru Baru,Kimia,08123,active\n"
        ."999,Guru Duplikat,Biologi,,active\n";

    $file = UploadedFile::fake()->createWithContent('guru.csv', $csv);

    $this->actingAs($this->admin)
        ->post(route('teachers.import'), ['file' => $file])
        ->assertRedirect();

    $this->assertDatabaseHas('teachers', ['nip' => '123', 'full_name' => 'Guru Baru']);
    $this->assertDatabaseHas('teachers', ['nip' => '999', 'full_name' => 'Guru Lama']);
});

it('imports the school teacher spreadsheet format without a nip column', function () {
    $csv = "NO,sdr/i,L/P,NAMA,(GTT)/ (GTY),JABATAN SEKARANG\n"
        ."1,Sdr.,L,ZIDAN SAYYID NUGRAHA,GTT,Guru Matematika\n"
        ."2,Sdri.,P,SITI AMINAH,GTY,Guru Bahasa Indonesia\n";

    $this->actingAs($this->admin)
        ->from(route('teachers.index'))
        ->post(route('teachers.import'), [
            'file' => UploadedFile::fake()->createWithContent('guru-sekolah.csv', $csv),
        ])
        ->assertRedirect(route('teachers.index'))
        ->assertSessionHas('success', fn (string $message) => str_contains($message, '2 guru ditambahkan'));

    $zidan = Teacher::where('full_name', 'ZIDAN SAYYID NUGRAHA')->firstOrFail();
    $siti = Teacher::where('full_name', 'SITI AMINAH')->firstOrFail();

    expect($zidan->nip)->toBeNull()
        ->and($zidan->gender)->toBe('L')
        ->and($zidan->jabatan)->toBe('Guru Matematika')
        ->and($zidan->mata_pelajaran)->toBeNull()
        ->and($siti->nip)->toBeNull()
        ->and($siti->gender)->toBe('P')
        ->and($siti->jabatan)->toBe('Guru Bahasa Indonesia')
        ->and($siti->mata_pelajaran)->toBeNull();
});

it('keeps mata pelajaran and jabatan in separate columns when both exist', function () {
    $csv = "NIP,Nama Lengkap,L/P,Mata Pelajaran,Jabatan,Telepon,Status\n"
        ."123,Guru Dua Kolom,P,Matematika,Wks. Kesiswaan,,active\n";

    $this->actingAs($this->admin)
        ->from(route('teachers.index'))
        ->post(route('teachers.import'), [
            'file' => UploadedFile::fake()->createWithContent('guru-dua-kolom.csv', $csv),
        ])
        ->assertRedirect(route('teachers.index'));

    $teacher = Teacher::where('nip', '123')->firstOrFail();

    expect($teacher->mata_pelajaran)->toBe('Matematika')
        ->and($teacher->jabatan)->toBe('Wks. Kesiswaan')
        ->and($teacher->gender)->toBe('P');
});

it('updates an existing teacher when the same nameless-nip row is imported again', function () {
    Teacher::create([
        'full_name' => 'KOKOM KOMALASARI, S.Pd., Gr',
        'gender' => 'P',
        'jabatan' => 'KOKOM LAMA',
        'status' => 'active',
    ]);

    $csv = "NIP,Nama Lengkap,L/P,Mata Pelajaran,Jabatan,Telepon,Status\n"
        .",\"KOKOM KOMALASARI, S.Pd., Gr\",P,GURU B. INDONESIA,Guru,,active\n";

    $this->actingAs($this->admin)
        ->from(route('teachers.index'))
        ->post(route('teachers.import'), [
            'file' => UploadedFile::fake()->createWithContent('guru-ulang.csv', $csv),
        ])
        ->assertRedirect(route('teachers.index'))
        ->assertSessionHas('success', fn (string $message) => str_contains($message, '1 diperbarui'));

    expect(Teacher::where('full_name', 'KOKOM KOMALASARI, S.Pd., Gr')->count())->toBe(1);

    $teacher = Teacher::where('full_name', 'KOKOM KOMALASARI, S.Pd., Gr')->firstOrFail();

    expect($teacher->mata_pelajaran)->toBe('GURU B. INDONESIA')
        ->and($teacher->jabatan)->toBe('Guru');
});

it('links a teacher record when creating a user account', function () {
    $teacher = Teacher::create(['full_name' => 'Guru Tanpa Akun', 'nip' => '321']);

    $this->actingAs($this->admin)
        ->post(route('users.store'), [
            'name' => 'Guru Tanpa Akun',
            'email' => 'guru321@example.com',
            'password' => 'password123',
            'role' => 'guru',
            'teacher_id' => $teacher->id,
        ])
        ->assertRedirect(route('users.index'));

    expect($teacher->fresh()->user_id)->not->toBeNull();
});

it('rejects teacher links for student accounts', function () {
    $teacher = Teacher::create(['full_name' => 'Guru X']);
    $student = Student::create([
        'nisn' => '1234567890',
        'nis' => '202412010001',
        'full_name' => 'Siswa X',
        'gender' => 'L',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->post(route('users.store'), [
            'name' => 'Siswa X',
            'email' => 'siswax@example.com',
            'password' => 'password123',
            'role' => 'siswa',
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
        ])
        ->assertSessionHasErrors('teacher_id');

    expect($teacher->fresh()->user_id)->toBeNull();
});

it('lets guru view the teacher list but not mutate it', function () {
    $guru = User::factory()->create();
    $guru->assignRole('guru');
    $teacher = Teacher::create(['full_name' => 'Guru Y']);

    $this->actingAs($guru)->get(route('teachers.index'))->assertOk();
    $this->actingAs($guru)->get(route('teachers.create'))->assertForbidden();
    $this->actingAs($guru)->post(route('teachers.store'), ['full_name' => 'Nope'])->assertForbidden();
    $this->actingAs($guru)->patch(route('teachers.toggle-status', $teacher))->assertForbidden();
});

it('blocks students from the teacher module', function () {
    $siswa = User::factory()->create();
    $siswa->assignRole('siswa');

    $this->actingAs($siswa)->get(route('teachers.index'))->assertForbidden();
});
