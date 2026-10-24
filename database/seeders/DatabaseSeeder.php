<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /** Seed the application's database. */
    public function run(): void
    {
        // Roles — 8 role: 7 existing + wali_kelas (RBAC baru, pakai SchoolClass.homeroom_teacher_id)
        foreach (['admin', 'guru', 'wali_kelas', 'bk', 'siswa', 'kesiswaan', 'kepala_sekolah'] as $peran) {
            Role::firstOrCreate(['name' => $peran, 'guard_name' => 'web']);
        }

        // 20 Guru & Staff (untuk dropdown wali kelas + data pengguna)
        $staffRoles = ['guru', 'guru', 'guru', 'guru', 'guru', 'guru', 'guru', 'guru', 'guru', 'guru',
                       'bk', 'bk', 'kesiswaan', 'kesiswaan', 'kesiswaan', 'kesiswaan', 'kesiswaan', 'kesiswaan', 'kesiswaan', 'kesiswaan'];
        $staffNames = ['Andi Pratama','Budi Santoso','Citra Lestari','Dewi Anggraini','Eko Wijaya',
                       'Fitri Rahmawati','Gilang Ramadhan','Hana Putri','Irfan Hidayat','Joko Susilo',
                       'Kartika Sari','Lukman Hakim','Maya Kusuma','Nadia Safitri','Oscar Tanujaya',
                       'Putri Ayu','Rizky Pratama','Sari Wulandari','Taufik Rahman','Umar Faruq'];
        $staffUsers = [];
        foreach ($staffNames as $i => $name) {
            $email = 'staff' . ($i + 1) . '@sekolah.sch.id';
            $u = User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => Hash::make('password')]);
            $u->syncRoles([$staffRoles[$i]]);
            $staffUsers[] = $u;
        }

        // Academic Year
        $academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026'],
            [
                'start_date' => now()->startOfYear(),
                'end_date'   => now()->startOfYear()->addYear()->subDay(),
                'status'     => AcademicYear::STATUS_ACTIVE,
                'is_locked'  => false,
                'is_active'  => true,
            ]
        );

        // Users
        $admin = User::firstOrCreate(['email' => 'admin@admin.com'], ['name' => 'Admin', 'password' => Hash::make('password')]);
        $admin->syncRoles(['admin']);

        $guru1 = User::firstOrCreate(['email' => 'guru1@guru.com'], ['name' => 'Guru 1', 'password' => Hash::make('password')]);
        $guru2 = User::firstOrCreate(['email' => 'guru2@guru.com'], ['name' => 'Guru 2', 'password' => Hash::make('password')]);
        foreach ([$guru1, $guru2] as $user) { $user->syncRoles(['guru']); }

        $bk = User::firstOrCreate(['email' => 'bk@bk.com'], ['name' => 'BK', 'password' => Hash::make('password')]);
        $bk->syncRoles(['bk']);

        // Kelas
        $classA = SchoolClass::firstOrCreate(
            ['name' => 'X RPL 1', 'academic_year_id' => $academicYear->id],
            ['grade_level' => 'X', 'major' => 'RPL', 'homeroom_teacher_id' => $guru1->id]
        );
        $classB = SchoolClass::firstOrCreate(
            ['name' => 'X RPL 2', 'academic_year_id' => $academicYear->id],
            ['grade_level' => 'X', 'major' => 'RPL', 'homeroom_teacher_id' => $guru2->id]
        );

        // 6 kelas tambahan (XII, XI, X) untuk test sort grade_level
        $classC = SchoolClass::firstOrCreate(
            ['name' => 'XII RPL 1', 'academic_year_id' => $academicYear->id],
            ['grade_level' => 'XII', 'major' => 'RPL', 'homeroom_teacher_id' => $staffUsers[0]->id]
        );
        $classD = SchoolClass::firstOrCreate(
            ['name' => 'XI RPL 1', 'academic_year_id' => $academicYear->id],
            ['grade_level' => 'XI', 'major' => 'RPL', 'homeroom_teacher_id' => $staffUsers[1]->id]
        );
        $classE = SchoolClass::firstOrCreate(
            ['name' => 'X TKJ 1', 'academic_year_id' => $academicYear->id],
            ['grade_level' => 'X', 'major' => 'TKJ', 'homeroom_teacher_id' => $staffUsers[2]->id]
        );
        $classF = SchoolClass::firstOrCreate(
            ['name' => 'XII TKJ 1', 'academic_year_id' => $academicYear->id],
            ['grade_level' => 'XII', 'major' => 'TKJ', 'homeroom_teacher_id' => $staffUsers[3]->id]
        );
        $classG = SchoolClass::firstOrCreate(
            ['name' => 'XI TKJ 1', 'academic_year_id' => $academicYear->id],
            ['grade_level' => 'XI', 'major' => 'TKJ', 'homeroom_teacher_id' => $staffUsers[4]->id]
        );
        $classH = SchoolClass::firstOrCreate(
            ['name' => 'X AKT 1', 'academic_year_id' => $academicYear->id],
            ['grade_level' => 'X', 'major' => 'AKT', 'homeroom_teacher_id' => $staffUsers[5]->id]
        );
        $allClasses = [$classA, $classB, $classC, $classD, $classE, $classF, $classG, $classH];

        // Siswa
        $siswa1User = User::firstOrCreate(['email' => 'siswa1@siswa.com'], ['name' => 'Siswa 1', 'password' => Hash::make('password')]);
        $siswa1User->syncRoles(['siswa']);
        $siswa1 = Student::firstOrCreate(
            ['nis' => 'NIS-001'],
            ['user_id' => $siswa1User->id, 'nisn' => '001', 'full_name' => 'Siswa 1', 'gender' => 'L', 'status' => 'active']
        );

        $siswa2User = User::firstOrCreate(['email' => 'siswa2@siswa.com'], ['name' => 'Siswa 2', 'password' => Hash::make('password')]);
        $siswa2User->syncRoles(['siswa']);
        $siswa2 = Student::firstOrCreate(
            ['nis' => 'NIS-002'],
            ['user_id' => $siswa2User->id, 'nisn' => '002', 'full_name' => 'Siswa 2', 'gender' => 'P', 'status' => 'active']
        );

        // Enrollment — siswa terdaftar di kelas melalui enrollment
        foreach ([[$siswa1, $classA], [$siswa2, $classA]] as [$siswa, $kelas]) {
            \App\Models\Enrollment::firstOrCreate(
                ['student_id' => $siswa->id, 'academic_year_id' => $academicYear->id],
                ['class_id' => $kelas->id, 'status' => 'active', 'enrolled_at' => now()]
            );
        }

        // 80 siswa test — tersebar di 8 kelas
        $firstNames = ['Adi','Budi','Citra','Dewi','Eko','Fitri','Gilang','Hana','Irfan','Joko',
                       'Kartika','Lukman','Maya','Nadia','Oscar','Putri','Rizky','Sari','Taufik','Umar',
                       'Vina','Wahyu','Yuni','Zahra','Agus','Bella','Candra','Dian','Eka','Fajar',
                       'Gita','Hendra','Intan','Jaya','Kirana','Lina','Made','Nugroho','Pandu','Qori',
                       'Rina','Sigit','Tono','Uci','Vino','Wulan','Yoga','Zaki','Arief','Bima',
                       'Cici','Doni','Erna','Fikri','Gadis','Hafiz','Ika','Johan','Kiki','Lutfi',
                       'Mega','Nanda','Oki','Puput','Rangga','Sinta','Teguh','Ulfah','Vera','Wahyu',
                       'Yusuf','Zaki','Aulia','Bagas','Cindy','Dimas','Evi','Fahmi','Gita','Hilman'];
        $lastNames = ['Pratama','Santoso','Lestari','Anggraini','Wijaya','Rahmawati','Ramadhan','Putri','Hidayat','Susilo',
                      'Sari','Hakim','Kusuma','Safitri','Tanujaya','Ayu','Pratama','Wulandah','Rahman','Faruq',
                      'Nugroho','Saputra','Utami','Prasetyo','Handayani','Kurniawan','Setiawan','Puspita','Hidayah','Firmansyah',
                      'Wibowo','Anggraini','Saputra','Pratama','Santoso','Lestari','Wijaya','Rahmawati','Ramadhan','Putri',
                      'Hidayat','Susilo','Sari','Hakim','Kusuma','Safitri','Tanujaya','Ayu','Pratama','Wulandah',
                      'Rahman','Faruq','Nugroho','Saputra','Utami','Prasetyo','Handayani','Kurniawan','Setiawan','Puspita',
                      'Hidayah','Firmansyah','Wibowo','Anggraini','Saputra','Pratama','Santoso','Lestari','Wijaya','Rahmawati',
                      'Ramadhan','Putri','Hidayat','Susilo','Sari','Hakim','Kusuma','Safitri','Tanujaya','Ayu'];
        $genders = ['L', 'P'];
        for ($i = 0; $i < 80; $i++) {
            $fn = $firstNames[$i];
            $ln = $lastNames[$i];
            $fullName = $fn . ' ' . $ln;
            $nisn = str_pad((string)(100 + $i), 10, '0', STR_PAD_LEFT);
            $nis = 'NIS-' . str_pad((string)(100 + $i), 3, '0', STR_PAD_LEFT);
            $gender = $genders[$i % 2];
            $classIdx = $i % 8;
            $class = $allClasses[$classIdx];

            $u = User::firstOrCreate(['email' => 'siswa' . ($i + 10) . '@sekolah.sch.id'], ['name' => $fullName, 'password' => Hash::make('password')]);
            $u->syncRoles(['siswa']);

            $student = Student::firstOrCreate(
                ['nis' => $nis],
                ['user_id' => $u->id, 'nisn' => $nisn, 'full_name' => $fullName, 'gender' => $gender, 'status' => 'active']
            );

            \App\Models\Enrollment::firstOrCreate(
                ['student_id' => $student->id, 'academic_year_id' => $academicYear->id],
                ['class_id' => $class->id, 'status' => 'active', 'enrolled_at' => now()]
            );
        }

        // Kategori pelanggaran & penghargaan TIDAK di-seed di sini.
        // Sumber tunggal: PancawaluyaSeeder (P1-P5 pelanggaran, R1-R10 penghargaan)
        // sesuai Pedoman Pendidikan Karakter Pancawaluya. Kode warisan a,b,c,d,e
        // tidak dipakai lagi.

        // Pedoman Pancawaluya (TABEL 1 penghargaan R1-R10 + TABEL 2 pelanggaran P1-P5).
        $this->call(PancawaluyaSeeder::class);
    }
}
