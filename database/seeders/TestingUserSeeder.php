<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Akun uji untuk memverifikasi RBAC tiap peran.
 *
 * Setiap akun guru/staf juga dibuatkan baris `teachers` agar halaman
 * Data Guru, tautan akun, dan kolom Identitas di daftar pengguna terisi.
 * Akun siswa diberi kelas + enrollment agar dashboard dan halaman siswa
 * tidak kosong.
 */
class TestingUserSeeder extends Seeder
{
    public function run(): void
    {
        $akun = [
            ['name' => 'Kepala Sekolah', 'email' => 'kepsek@kepsek.com', 'role' => 'kepala_sekolah'],
            ['name' => 'BK', 'email' => 'bk@bk.com', 'role' => 'bk'],
            ['name' => 'Kesiswaan', 'email' => 'kesiswaan@kesiswaan.com', 'role' => 'kesiswaan'],
            ['name' => 'Guru', 'email' => 'guru@guru.com', 'role' => 'guru'],
            ['name' => 'Wali Kelas', 'email' => 'wali@wali.com', 'role' => 'wali_kelas'],
            ['name' => 'Siswa', 'email' => 'siswa@siswa.com', 'role' => 'siswa'],
        ];

        $users = [];

        foreach ($akun as $data) {
            Role::firstOrCreate(['name' => $data['role'], 'guard_name' => 'web']);

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')]
            );
            $user->syncRoles([$data['role']]);

            $users[$data['role']] = $user;
        }

        // Data guru untuk tiap akun guru/staf — dipakai halaman /teachers dan
        // kolom identitas di /users. Idempoten lewat user_id.
        $guru = [
            [
                'role' => 'kepala_sekolah',
                'nip' => '1000000001',
                'gender' => 'L',
                'mata_pelajaran' => null,
                'jabatan' => 'Kepala Sekolah',
            ],
            [
                'role' => 'kesiswaan',
                'nip' => '1000000002',
                'gender' => 'L',
                'mata_pelajaran' => null,
                'jabatan' => 'Kesiswaan',
            ],
            [
                'role' => 'bk',
                'nip' => '1000000003',
                'gender' => 'P',
                'mata_pelajaran' => null,
                'jabatan' => 'bk',
            ],
            [
                'role' => 'guru',
                'nip' => '1000000004',
                'gender' => 'P',
                'mata_pelajaran' => 'GURU B. INDONESIA',
                'jabatan' => 'Guru',
            ],
            [
                'role' => 'wali_kelas',
                'nip' => '1000000005',
                'gender' => 'L',
                'mata_pelajaran' => 'GURU MATEMATIKA',
                'jabatan' => 'Wali Kelas',
            ],
        ];

        foreach ($guru as $data) {
            $user = $users[$data['role']];

            $teacher = Teacher::firstOrNew(['user_id' => $user->id]);

            // Jangan rebut NIP milik guru lain (import data asli bisa memakai
            // nomor yang sama); baris uji tetap dibuat tanpa NIP.
            $nipTaken = Teacher::where('nip', $data['nip'])
                ->when($teacher->exists, fn ($query) => $query->whereKeyNot($teacher->getKey()))
                ->exists();

            $teacher->fill([
                'nip' => $nipTaken ? null : $data['nip'],
                'full_name' => $user->name,
                'gender' => $data['gender'],
                'mata_pelajaran' => $data['mata_pelajaran'],
                'jabatan' => $data['jabatan'],
                'status' => 'active',
            ])->save();
        }

        // Akun siswa juga perlu data Student agar halaman siswa berfungsi.
        $siswaUser = $users['siswa'];

        $siswa = Student::updateOrCreate(
            ['nis' => 'NIS-TEST-001'],
            [
                'user_id' => $siswaUser->id,
                'nisn' => '0000000001',
                'full_name' => 'Siswa Test',
                'gender' => 'L',
                'status' => 'active',
            ]
        );

        // Kelas + enrollment hanya bila ada tahun ajaran (dibuat DatabaseSeeder).
        $academicYear = AcademicYear::where('is_active', true)->latest('id')->first()
            ?? AcademicYear::orderByDesc('start_date')->first();

        if ($academicYear === null) {
            return;
        }

        $kelas = SchoolClass::firstOrCreate(
            ['name' => 'VII A', 'academic_year_id' => $academicYear->id],
            [
                'grade_level' => 'VII',
                'capacity' => 32,
                'status' => 'active',
                'homeroom_teacher_id' => $users['wali_kelas']->id,
            ]
        );

        // Kelas asli yang sudah punya wali kelas tidak ditimpa; hanya kelas
        // tanpa wali (atau yang walinya akun uji) yang diisi akun uji.
        $testUserIds = array_map(fn (User $user) => $user->id, $users);

        if ($kelas->homeroom_teacher_id === null || in_array($kelas->homeroom_teacher_id, $testUserIds, true)) {
            $kelas->update(['homeroom_teacher_id' => $users['wali_kelas']->id]);
        }

        Enrollment::updateOrCreate(
            ['student_id' => $siswa->id, 'academic_year_id' => $academicYear->id],
            [
                'class_id' => $kelas->id,
                'status' => 'active',
                'enrolled_at' => now(),
            ]
        );
    }
}
