# Spesifikasi Teknis Basis Data (Database Dictionary)

Dokumen ini berisi kamus data lengkap (*data dictionary*), definisi tipe data, batasan (*constraints*), dan strategi pengindeksan (*indexing*) pada basis data **Pelanggaran Siswa**.

---

## 1. Konvensi dan Kebijakan Basis Data

- **DBMS Target**: MariaDB 10.6+ / MySQL 8.0+
- **Karakter Set & Collation**: `utf8mb4_unicode_ci`
- **Tipe Primary Key**: `BIGINT UNSIGNED AUTO_INCREMENT` (kecuali tabel `settings` yang menggunakan string key)
- **Foreign Key Action**:
  - `cascadeOnDelete()`: Berlaku untuk data anak langsung yang bergantung total pada induk (contoh: `enrollments` saat `students` dihapus).
  - `restrictOnDelete()`: Berlaku untuk data aturan acuan (contoh: kategori pelanggaran tidak boleh dihapus jika masih ada kasus yang merujuk).
  - `nullOnDelete()`: Berlaku untuk penandatangan / staf pembina (contoh: jika user dihapus, nama pada kasus lama menjadi null tanpa menghapus kasus).
- **Audit Immutability**: Tabel `point_ledgers` dilarang keras menerima query `UPDATE` atau `DELETE` pada tingkat aplikasi. Perbaikan data wajib menggunakan transaksi pembalik bertipe `REVERSAL`.

---

## 2. Kamus Data Entitas (12 Tabel Utama)

### 2.1 `users`
Menyimpan akun pengguna sistem (staf sekolah dan siswa).

| Kolom | Tipe Data | Nullable | Keterangan / Constraint |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary Key |
| `name` | `VARCHAR(255)` | Tidak | Nama lengkap pengguna |
| `email` | `VARCHAR(255)` | Tidak | Unique email login |
| `nip` | `VARCHAR(40)` | Ya | Nomor Induk Pegawai staf (opsional) |
| `avatar_path` | `VARCHAR(255)` | Ya | Jalur berkas foto profil (`storage/avatars/...`) |
| `password` | `VARCHAR(255)` | Tidak | Hash bcrypt kata sandi |
| `email_verified_at` | `TIMESTAMP` | Ya | Waktu verifikasi email |
| `remember_token` | `VARCHAR(100)` | Ya | Token "ingat saya" |
| `created_at` | `TIMESTAMP` | Ya | Waktu pembuatan |
| `updated_at` | `TIMESTAMP` | Ya | Waktu penyuntingan |
| `deleted_at` | `TIMESTAMP` | Ya | Soft delete timestamp |

---

### 2.2 `academic_years`
Menyimpan periode tahun ajaran aktif dan arsip akademik.

| Kolom | Tipe Data | Nullable | Keterangan / Constraint |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary Key |
| `name` | `VARCHAR(20)` | Tidak | Format penamaan: `2026/2027` |
| `start_date` | `DATE` | Tidak | Tanggal awal tahun ajaran |
| `end_date` | `DATE` | Ya | Tanggal berakhir tahun ajaran |
| `status` | `VARCHAR(20)` | Tidak | `draft`, `active`, `closed`, `archived` |
| `is_active` | `BOOLEAN` | Tidak | `true` jika tahun ajaran aktif berjalan (default `false`) |
| `is_locked` | `BOOLEAN` | Tidak | `true` jika data nilai & poin periode sudah dikunci permanen |
| `created_at` | `TIMESTAMP` | Ya | - |
| `updated_at` | `TIMESTAMP` | Ya | - |

---

### 2.3 `classes`
Menyimpan data rombongan belajar (rombel kelas).

| Kolom | Tipe Data | Nullable | Keterangan / Constraint |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary Key |
| `academic_year_id` | `BIGINT UNSIGNED` | Tidak | Foreign Key ke `academic_years.id` (Cascade) |
| `name` | `VARCHAR(20)` | Tidak | Nama kelas, contoh: `X PPLG 1`, `XII DKV 2` |
| `grade_level` | `ENUM('X','XI','XII')` | Ya | Tingkat angkatan |
| `major` | `VARCHAR(50)` | Ya | Konsentrasi keahlian (contoh: `PPLG`, `DKV`, `AKL`) |
| `capacity` | `INTEGER UNSIGNED` | Tidak | Kapasitas maksimal kursi (default `40`) |
| `homeroom_teacher_id` | `BIGINT UNSIGNED` | Ya | Foreign Key ke `users.id` (Wali Kelas) |
| `assistant_teacher_id`| `BIGINT UNSIGNED` | Ya | Foreign Key ke `users.id` (Guru Pendamping) |
| `status` | `ENUM('draft','active')` | Tidak | Status rombel (default `active`) |
| `created_at` | `TIMESTAMP` | Ya | - |
| `updated_at` | `TIMESTAMP` | Ya | - |

*Index Unik*: `UNIQUE (academic_year_id, name)` — Mencegah nama kelas ganda pada tahun ajaran yang sama.

---

### 2.4 `students`
Menyimpan profil identitas dasar siswa sekolah.

| Kolom | Tipe Data | Nullable | Keterangan / Constraint |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary Key |
| `user_id` | `BIGINT UNSIGNED` | Ya | Foreign Key ke `users.id` (Akun login murid, Set Null) |
| `nisn` | `VARCHAR(20)` | Tidak | Unique 10 digit NISN Kemendikbud |
| `nis` | `VARCHAR(20)` | Tidak | Unique Nomor Induk Siswa Sekolah |
| `full_name` | `VARCHAR(255)` | Tidak | Nama lengkap siswa |
| `gender` | `ENUM('L','P')` | Tidak | Jenis kelamin |
| `birth_date` | `DATE` | Ya | Tanggal lahir |
| `phone` | `VARCHAR(20)` | Ya | Nomor telepon / WhatsApp siswa |
| `address` | `TEXT` | Ya | Alamat tempat tinggal |
| `parent_name` | `VARCHAR(255)` | Ya | Nama orang tua / wali |
| `parent_phone` | `VARCHAR(20)` | Ya | Kontak darurat orang tua |
| `avatar_path` | `VARCHAR(255)` | Ya | Pasfoto siswa |
| `status` | `ENUM('active','inactive','graduated','transferred')` | Tidak | Status kesiswaan (default `active`) |
| `created_at` | `TIMESTAMP` | Ya | - |
| `updated_at` | `TIMESTAMP` | Ya | - |

---

### 2.5 `enrollments`
**Tabel Sumber Kebenaran Tunggal (*Single Source of Truth*)** yang menghubungkan siswa dengan kelas dan tahun ajaran.

| Kolom | Tipe Data | Nullable | Keterangan / Constraint |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary Key |
| `student_id` | `BIGINT UNSIGNED` | Tidak | Foreign Key ke `students.id` (Cascade) |
| `class_id` | `BIGINT UNSIGNED` | Tidak | Foreign Key ke `classes.id` (Cascade) |
| `academic_year_id` | `BIGINT UNSIGNED` | Tidak | Foreign Key ke `academic_years.id` (Cascade) |
| `status` | `ENUM('active','completed','graduated','withdrawn','transferred')` | Tidak | Status pendaftaran (default `active`) |
| `enrolled_at` | `DATE` | Ya | Tanggal mulai masuk kelas |
| `ended_at` | `DATE` | Ya | Tanggal selesai / naik kelas |
| `created_at` | `TIMESTAMP` | Ya | - |
| `updated_at` | `TIMESTAMP` | Ya | - |

*Index & Kunci*:
- `UNIQUE (student_id, academic_year_id)`: Siswa hanya boleh terdaftar di 1 kelas per tahun ajaran.
- `INDEX (academic_year_id, class_id)`: Kecepatan lookup siswa satu kelas.
- `INDEX (student_id, status)`: Kecepatan filter enrollment aktif.

---

### 2.6 `violation_categories`
Katalog hierarki pelanggaran tata tertib (Pedoman Pancawaluya).

| Kolom | Tipe Data | Nullable | Keterangan / Constraint |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary Key |
| `parent_id` | `BIGINT UNSIGNED` | Ya | Foreign Key ke `violation_categories.id` (Induk kategori) |
| `code` | `VARCHAR(20)` | Tidak | Unique kode aturan (contoh: `TRM-001`, `KHD-002`) |
| `name` | `VARCHAR(150)` | Tidak | Nama pelanggaran |
| `group_type` | `VARCHAR(50)` | Tidak | Kelompok aturan (contoh: `terlambat`, `kehadiran`, `seragam`) |
| `severity` | `ENUM('ringan','sedang','berat','sangat_berat')` | Tidak | Skala: 8, 16, 32, 64 (Zero Tolerance) |
| `points` | `INTEGER UNSIGNED` | Tidak | Bobot poin penalti dasar |
| `is_recurring` | `BOOLEAN` | Tidak | `true` jika menerapkan eskalasi toleransi bertingkat |
| `recurring_increment` | `INTEGER UNSIGNED` | Ya | Poin penalti pengulangan ke-2 |
| `max_recurring_points`| `INTEGER UNSIGNED` | Ya | Batas penalti toleransi ke-3 (maksimum) |
| `sanksi` | `VARCHAR(255)` | Ya | Deskripsi tindakan pembinaan |
| `status` | `ENUM('draft','active')` | Tidak | Status kategori (default `draft`) |
| `created_at` | `TIMESTAMP` | Ya | - |
| `updated_at` | `TIMESTAMP` | Ya | - |

---

### 2.7 `achievement_categories`
Katalog apresiasi prestasi dan penghargaan (10 Pilar Pancawaluya).

| Kolom | Tipe Data | Nullable | Keterangan / Constraint |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary Key |
| `parent_id` | `BIGINT UNSIGNED` | Ya | Foreign Key ke `achievement_categories.id` |
| `code` | `VARCHAR(20)` | Tidak | Unique kode prestasi (contoh: `PST-001`, `ACH-010`) |
| `name` | `VARCHAR(150)` | Tidak | Nama prestasi |
| `points` | `INTEGER UNSIGNED` | Tidak | Bobot poin apresiasi (penambah saldo) |
| `group_type` | `VARCHAR(50)` | Tidak | Kelompok pilar prestasi (contoh: `akademik`, `sosial`) |
| `status` | `ENUM('draft','active')` | Tidak | Status kategori |
| `created_at` | `TIMESTAMP` | Ya | - |
| `updated_at` | `TIMESTAMP` | Ya | - |

---

### 2.8 `redemption_rules`
Tabel pemetaan kanonik untuk aturan penebusan restoratif poin pelanggaran.

| Kolom | Tipe Data | Nullable | Keterangan / Constraint |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary Key |
| `violation_category_id` | `BIGINT UNSIGNED` | Tidak | Foreign Key ke `violation_categories.id` (Cascade) |
| `achievement_category_id` | `BIGINT UNSIGNED` | Tidak | Foreign Key ke `achievement_categories.id` (Cascade) |
| `created_at` | `TIMESTAMP` | Ya | - |
| `updated_at` | `TIMESTAMP` | Ya | - |

*Catatan Bisnis*: Pelanggaran bertingkat *Sangat Berat* (*Zero Tolerance*) tidak memiliki relasi di tabel ini karena sanksinya bersifat administratif langsung (bukan penugasan penebusan mandiri).

---

### 2.9 `discipline_cases`
Berkas transaksi kasus pelanggaran siswa.

| Kolom | Tipe Data | Nullable | Keterangan / Constraint |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary Key |
| `case_number` | `VARCHAR(30)` | Tidak | Unique nomor kasus resmi: `KAS-YYYY-XXXX` |
| `student_id` | `BIGINT UNSIGNED` | Tidak | Foreign Key ke `students.id` (Cascade) |
| `violation_category_id` | `BIGINT UNSIGNED` | Tidak | Foreign Key ke `violation_categories.id` (Restrict) |
| `enrollment_id` | `BIGINT UNSIGNED` | Ya | Snapshot ID pendaftaran saat kejadian |
| `class_id` | `BIGINT UNSIGNED` | Ya | Snapshot ID kelas saat kejadian |
| `academic_year_id` | `BIGINT UNSIGNED` | Ya | Snapshot ID tahun ajaran saat kejadian |
| `report_by` | `BIGINT UNSIGNED` | Ya | Foreign Key ke `users.id` (Staf pelapor kejadian) |
| `status` | `VARCHAR(20)` | Tidak | `found`, `handling`, `closed`, `dismissed` |
| `incident_date` | `DATE` | Ya | Tanggal terjadinya pelanggaran |
| `incident_time` | `TIME` | Ya | Waktu terjadinya pelanggaran |
| `location` | `VARCHAR(100)` | Ya | Lokasi kejadian di lingkungan sekolah |
| `description` | `TEXT` | Tidak | Kronologi kejadian insiden |
| `validated_by` | `BIGINT UNSIGNED` | Ya | Staf yang memajukan kasus ke tahap `handling` |
| `validated_at` | `TIMESTAMP` | Ya | Waktu pemotongan poin dieksekusi |
| `created_at` | `TIMESTAMP` | Ya | - |
| `updated_at` | `TIMESTAMP` | Ya | - |

*Index*:
- `INDEX (student_id, status)`: Optimasi query dashboard & riwayat per siswa.
- `INDEX (student_id, violation_category_id, created_at)`: Optimasi perhitungan tangga eskalasi toleransi ke-1, ke-2, ke-3.

---

### 2.10 `achievement_records`
Catatan capaian prestasi dan kegiatan penebusan poin siswa.

| Kolom | Tipe Data | Nullable | Keterangan / Constraint |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary Key |
| `student_id` | `BIGINT UNSIGNED` | Tidak | Foreign Key ke `students.id` (Cascade) |
| `achievement_category_id` | `BIGINT UNSIGNED` | Tidak | Foreign Key ke `achievement_categories.id` (Restrict) |
| `discipline_case_id` | `BIGINT UNSIGNED` | Ya | Foreign Key ke `discipline_cases.id` (Kasus yang ditebus) |
| `enrollment_id` | `BIGINT UNSIGNED` | Ya | Snapshot ID pendaftaran saat prestasi diraih |
| `class_id` | `BIGINT UNSIGNED` | Ya | Snapshot ID kelas saat prestasi diraih |
| `academic_year_id` | `BIGINT UNSIGNED` | Ya | Snapshot ID tahun ajaran aktif |
| `recorded_by` | `BIGINT UNSIGNED` | Tidak | Foreign Key ke `users.id` (Staf pencatat prestasi) |
| `event_name` | `VARCHAR(255)` | Ya | Nama lomba / kegiatan pembiasaan |
| `event_date` | `DATE` | Ya | Tanggal diraihnya prestasi |
| `achievement_level` | `VARCHAR(50)` | Ya | Tingkat: `sekolah`, `kota`, `provinsi`, `nasional` |
| `points_awarded` | `INTEGER UNSIGNED` | Ya | Poin kredit yang disetujui |
| `status` | `ENUM('pending','verified','rejected')` | Tidak | Status verifikasi prestasi |
| `verified_by` | `BIGINT UNSIGNED` | Ya | Staf yang menyetujui klaim prestasi |
| `verified_at` | `TIMESTAMP` | Ya | Waktu persetujuan |
| `description` | `TEXT` | Ya | Catatan tambahan |
| `created_at` | `TIMESTAMP` | Ya | - |
| `updated_at` | `TIMESTAMP` | Ya | - |

---

### 2.11 `point_ledgers`
**Buku Besar Mutasi Poin Append-Only (*Immutable Ledger*)**.

| Kolom | Tipe Data | Nullable | Keterangan / Constraint |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | Primary Key (Monotonik naik) |
| `student_id` | `BIGINT UNSIGNED` | Tidak | Foreign Key ke `students.id` (Cascade) |
| `academic_year_id` | `BIGINT UNSIGNED` | Tidak | Foreign Key ke `academic_years.id` (Cascade) |
| `direction` | `ENUM('credit','debit')` | Tidak | Arah mutasi (`credit` = nambah, `debit` = kurang) |
| `amount` | `INTEGER UNSIGNED` | Tidak | Besaran nominal mutasi poin |
| `balance_after` | `INTEGER` | Tidak | Saldo akhir setelah transaksi dieksekusi |
| `transaction_type`| `ENUM(...)` | Tidak | `OPENING_BALANCE`, `ACHIEVEMENT`, `VIOLATION`, `RECOVERY`, `REVERSAL` |
| `source_type` | `VARCHAR(255)` | Ya | Polymorphic class (`DisciplineCase`, `AchievementRecord`) |
| `source_id` | `BIGINT UNSIGNED` | Ya | Polymorphic ID referensi transaksi asal |
| `reason` | `VARCHAR(255)` | Ya | Keterangan ringkas mutasi buku besar |
| `created_by` | `BIGINT UNSIGNED` | Ya | Foreign Key ke `users.id` (Operator mutasi) |
| `verified_by` | `BIGINT UNSIGNED` | Ya | Foreign Key ke `users.id` (Pejabat pengesah) |
| `verified_at` | `TIMESTAMP` | Ya | Waktu pengesahan mutasi |
| `created_at` | `TIMESTAMP` | Ya | Timestamp pencatatan transaksi |

*Index & Integritas*:
- `INDEX (student_id, academic_year_id)`: Kecepatan query saldo per periode.
- `INDEX (student_id, academic_year_id, created_at)`: Audit pergerakan saldo berurutan.
- `UNIQUE (student_id, academic_year_id, transaction_type)` khusus `OPENING_BALANCE`: Mencegah pemberian saldo awal 2000 ganda dalam satu tahun ajaran.
- `UNIQUE (source_type, source_id)`: Idempotensi mutasi. Satu kasus pelanggaran atau satu rekam prestasi hanya dapat memotong/menambah poin tepat satu kali.

---

### 2.12 `settings`
Menyimpan konfigurasi instansi, kop surat, tanda tangan digital, dan footer sertifikat.

| Kolom | Tipe Data | Nullable | Keterangan / Constraint |
| :--- | :--- | :--- | :--- |
| `key` | `VARCHAR(191)` | Tidak | Primary Key (nama variabel) |
| `value` | `TEXT` | Ya | Nilai konfigurasi |
| `created_at` | `TIMESTAMP` | Ya | - |
| `updated_at` | `TIMESTAMP` | Ya | - |
