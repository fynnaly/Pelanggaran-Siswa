# Arsitektur Perangkat Lunak dan Alur Data

## 1. Ikhtisar Arsitektur
Aplikasi Pelanggaran Siswa dibangun menggunakan **Laravel 13** dengan pendekatan *Monolithic Model-View-Controller (MVC)* yang diperkaya *Domain Service Layer* dan komponen antarmuka *Blade Component-Driven System*. Sistem juga menyediakan antarmuka *RESTful API* terisolasi pada rute `/api/v1` untuk aplikasi pendamping Android (Pelanggaran Kuy).

```text
+-----------------------------------------------------------------------------------+
|                                 Presentation Layer                                |
|   Blade Templates (x-app-layout)  |  Tailwind CSS & CSS Tokens  |  Alpine.js DOM  |
+-----------------------------------------------------------------------------------+
                                          |
                                   HTTP / Middleware
                     (auth, role, prevent-back, verified)
                                          |
+-----------------------------------------------------------------------------------+
|                                 Application Layer                                 |
|      Resource Controllers  |  Form Requests  |  Policies (Gate Authorization)     |
+-----------------------------------------------------------------------------------+
                                          |
+-----------------------------------------------------------------------------------+
|                                   Domain Layer                                    |
|     Academic Services  |  Discipline State Engine  |  Ledger Transaction Engine   |
+-----------------------------------------------------------------------------------+
                                          |
+-----------------------------------------------------------------------------------+
|                                Persistence Layer                                  |
|         Eloquent ORM Models  |  Database Transactions & Locks  |  MariaDB/MySQL   |
+-----------------------------------------------------------------------------------+
```

---

## 2. Struktur Direktori dan Komponen

```text
app/
|-- Http/
|   |-- Controllers/
|   |   |-- AcademicYearController.php     # Manajemen tahun ajaran & pemicu promosi
|   |   |-- AchievementCategoryController.php # Master kategori prestasi
|   |   |-- AchievementRecordController.php   # Transaksi pencatatan & verifikasi prestasi
|   |   |-- DashboardController.php        # Metrik KPI, agregasi saldo, & chart distribusi
|   |   |-- DisciplineCaseController.php   # Manajemen siklus kasus pelanggaran
|   |   |-- ProfileController.php          # Profil staf, avatar, password, & zona bahaya
|   |   |-- SchoolClassController.php      # Manajemen rombel kelas
|   |   |-- SettingController.php          # Konfigurasi legalitas sekolah & sertifikat
|   |   |-- StudentController.php          # Master siswa, template, impor/ekspor XLSX/CSV
|   |   |-- ViolationCategoryController.php# Master kategori pelanggaran & eskalasi
|   |   `-- Api/                           # Controller khusus API Android (/api/v1)
|   |-- Middleware/                        # Role filter, cache control, prevent back
|   `-- Requests/                          # Form request validation
|-- Models/                                # Eloquent models (Student, PointLedger, etc.)
|-- Policies/                              # Policy otorisasi aksi domain (DisciplineCasePolicy)
`-- Services/
    `-- Academic/
        `-- PromotionService.php           # Logika kenaikan kelas, carry forward saldo
```

---

## 3. Alur State Machine Kasus Pelanggaran
Siklus penanganan kasus pelanggaran dirancang efisien dalam 3 tahap transisi utama:

```text
      +-------------------------------------------------------+
      |                        Laporan                        |
      |   Guru / Staf mencatat temuan kejadian di lapangan    |
      +-------------------------------------------------------+
                                  |
                                  v
      +-------------------------------------------------------+
      |                    Status: [found]                    |
      | - Berkas tercatat di sistem                           |
      | - Saldo poin siswa BELUM berkurang                    |
      | - Kasus dapat ditolak/dibuang (dismissed) jika keliru |
      +-------------------------------------------------------+
                                  |
                   Verifikasi oleh BK / Kesiswaan
                                  |
                                  v
      +-------------------------------------------------------+
      |                  Status: [handling]                   |
      | - Eksekusi pemotongan saldo poin (PointLedger DEBIT)  |
      | - Tangga toleransi dihitung otomatis                  |
      | - Rencana pembinaan / tindakan preventif diterapkan   |
      +-------------------------------------------------------+
                                  |
                 Siswa menyelesaikan masa pembinaan
                                  |
                                  v
      +-------------------------------------------------------+
      |                   Status: [closed]                    |
      | - Kasus selesai secara administratif                  |
      | - Berkas terkunci sebagai arsip historis              |
      +-------------------------------------------------------+
```

### Invarian Transisi Status:
1. **Pemisahan Pelaporan dari Pemotongan**: Staf pelapor tidak memotong poin secara sepihak saat pelaporan. Poin hanya terpotong setelah staf berwenang (BK/Kesiswaan/Admin) memajukan kasus ke tahap `handling`.
2. **One-Way Status Advancement**: Status kasus hanya boleh bergerak maju (`found` -> `handling` -> `closed`). Kasus yang sudah berada di `handling` tidak dapat dibuang (`dismissed`).
3. **Idempotensi Ledger**: Pemotongan saldo poin ke `point_ledgers` hanya dieksekusi tepat satu kali ketika kasus pertama kali masuk ke tahap `handling` (`debitCase`).

---

## 4. Mekanisme Atomik Point Ledger

Pencatatan mutasi poin dijalankan di dalam transaksi database dengan penguncian eksklusif baris (`lockForUpdate`):

```php
DB::transaction(function () use ($case, $points, $operatorId) {
    // 1. Kunci baris ledger terakhir milik siswa pada tahun ajaran aktif
    $lastEntry = PointLedger::where('student_id', $case->student_id)
        ->where('academic_year_id', $case->academic_year_id)
        ->lockForUpdate()
        ->latest('id')
        ->first();

    // 2. Hitung saldo baru (fallback 2000 jika belum ada baris)
    $currentBalance = $lastEntry ? $lastEntry->balance_after : 2000;
    $newBalance = $currentBalance - $points;

    // 3. Masukkan baris mutasi baru (Append-Only)
    PointLedger::create([
        'student_id'        => $case->student_id,
        'academic_year_id'  => $case->academic_year_id,
        'direction'         => 'debit',
        'amount'            => $points,
        'balance_after'     => $newBalance,
        'transaction_type'  => 'VIOLATION',
        'source_type'       => DisciplineCase::class,
        'source_id'         => $case->id,
        'reason'            => "Pelanggaran: {$case->violationCategory->name}",
        'created_by'        => $operatorId,
        'verified_by'       => $operatorId,
        'verified_at'       => now(),
    ]);
});
```

Keunggulan pola ini:
- **Race-Condition Free**: Jika dua staf melakukan validasi terhadap siswa yang sama pada milidetik yang sama, transaksi kedua menunggu kunci dilepas dan membaca `$currentBalance` terbaru.
- **Audit Forester**: Nilai `balance_after` selalu mencerminkan urutan waktu yang tidak terbantahkan.

---

## 5. Keamanan Aplikasi dan Pertahanan Sistem

### 5.1 Otorisasi Berlapis (Defense in Depth)
- **Middleware Level**: `auth` dan `role:admin,bk,kesiswaan` mengunci akses endpoint routing di `routes/web.php`.
- **Controller Level**: `$this->authorize('update', $case)` memanggil `DisciplineCasePolicy` sebelum logika dijalankan.
- **View Level**: Direktif `@can` dan kondisi `@if(auth()->user()->hasRole(...))` menyembunyikan kontrol aksi destruktif dari tampilan pengguna non-staf.

### 5.2 Anti-Caching & Logout Isolation (`PreventBackHistory`)
Setelah staf logout, browser dicegah menampilkan cache halaman authenticated saat tombol back ditekan melalui header HTTP ketat:
```text
Cache-Control: no-cache, no-store, must-revalidate, max-age=0
Pragma: no-cache
Expires: Sat, 01 Jan 2000 00:00:00 GMT
```

### 5.3 Perlindungan Integritas Hapus Akun (Soft Delete & Verifikasi Ketik Nama)
- Model `User` menggunakan `SoftDeletes`. Menghapus user tidak memicu `ON DELETE CASCADE` atau `SET NULL` yang merusak histori pelapor/verifikator pada kasus lampau.
- Form penghapusan akun menerapkan friction konfirmasi pengetikan nama akun secara verbatim (GitHub deletion pattern).

---

## 6. Arsitektur Otomasi CI/CD (GitHub Actions)
Pipeline integrasi berkelanjutan didefinisikan pada `.github/workflows/pull-request.yml` yang terpicu saat pembuatan Pull Request ke cabang `master`:
1. **Environment Matrix**: Runner Ubuntu dengan PHP 8.4 dan Node.js 20.
2. **Dependency Resolution**: Cache Composer dan NPM berdasar hash file kunci (`composer.lock`, `package-lock.json`).
3. **View Pre-compilation**: Validasi sintaks Blade tanpa eksekusi browser (`php artisan view:cache`).
4. **Automated Test Suite**: Eksekusi Pest/PHPUnit untuk memverifikasi alur otentikasi, profil, siklus kasus pelanggaran, dan mutasi ledger.
