# Perancangan Sistem Basis Data dan Domain Model

## 1. Konsep Dasar Perancangan
Perancangan aplikasi Pelanggaran Siswa berfokus pada pemodelan domain pendidikan kejuruan yang mempertahankan akurasi historis dan kepatuhan audit. Sistem ini memisahkan secara tegas antara data master akademik, data aturan/referensi, data transaksi operasional, dan data mutasi buku kas (ledger).

---

## 2. Aktor Sistem dan Matriks Hak Akses (RBAC)
Sistem menggunakan Spatie Laravel-Permission dengan 6 peran utama:

| Peran (Role) | Tanggung Jawab Utama | Akses Laporan Kasus | Akses Prestasi | Akses Master & Konfigurasi |
| :--- | :--- | :--- | :--- | :--- |
| **admin** | Pemeliharaan sistem, manajemen pengguna, migrasi tahun ajaran, dan konfigurasi global | Buat, Proses, Hapus Kasus | Buat, Verifikasi | Full Access seluruh menu |
| **guru** | Pencatatan temuan pelanggaran awal di lapangan, input prestasi siswa binaan | Buat Kasus (Temuan) | Rekam Prestasi | Read-only Siswa & Kelas |
| **bk** | Bimbingan konseling, penanganan kasus, pemanggilan orang tua, verifikasi prestasi | Buat, Proses ke Handling & Closed | Buat, Verifikasi Penebusan | Read-only Data Referensi |
| **kesiswaan** | Penegakan tata tertib, penanganan zona kritis, approval sanksi berat, monitoring | Buat, Proses, Penanganan Kasus | Buat, Verifikasi | Kelola Kategori & Kelas |
| **kepala_sekolah** | Pengawasan menyeluruh, penerbitan surat keputusan pembinaan khusus dan sertifikat | Monitoring & Review Kasus | Monitoring Prestasi | Laporan & Ekspor Data |
| **siswa** | Pemeriksaan saldo poin, pemantauan riwayat pelanggaran dan apresiasi prestasi pribadi | Read-only akun pribadi | Read-only akun pribadi | Tidak ada akses dashboard staf |

---

## 3. Entitas dan Struktur Basis Data

### 3.1 Master Data Akademik & Identitas
1. **`users`**
   - Menyimpan kredensial staf dan pengguna sistem.
   - Kolom: `id`, `name`, `email`, `avatar_path`, `nip`, `password`, `remember_token`, `created_at`, `updated_at`, `deleted_at` (SoftDeletes).
2. **`academic_years`**
   - Tahun ajaran aktif yang menjadi filter utama seluruh query kelas dan pendaftaran.
   - Kolom: `id`, `name` (e.g. `2026/2027`), `start_date`, `end_date`, `status` (`draft`, `active`, `closed`, `archived`), `is_active` (boolean), `is_locked` (boolean).
3. **`classes` (`SchoolClass`)**
   - Data rombongan belajar (rombel).
   - Kolom: `id`, `academic_year_id` (FK), `name` (e.g. `X PPLG 1`), `grade_level` (`X`, `XI`, `XII`), `major` (Program Keahlian, e.g. `PPLG`, `DKV`), `homeroom_teacher_id` (FK `users`, Wali Kelas), `assistant_teacher_id` (FK `users`, opsional), `capacity`.
4. **`students`**
   - Data profil statis siswa (independen dari kelas/tahun ajaran).
   - Kolom: `id`, `user_id` (FK `users`, nullable), `full_name`, `nis`, `nisn`, `gender` (`L`/`P`), `birth_date`, `phone`, `parent_name`, `parent_phone`, `address`, `status` (`active`, `graduated`, `transferred`, `dropped_out`).
5. **`enrollments`**
   - **Source of truth relasi siswa dan kelas**. Siswa terdaftar di kelas mana pada tahun ajaran apa.
   - Kolom: `id`, `student_id` (FK), `class_id` (FK), `academic_year_id` (FK), `status` (`active`, `graduated`, `transferred`, `withdrawn`), `enrolled_at`, `ended_at`.

### 3.2 Master Aturan & Referensi (Pedoman Pancawaluya)
6. **`violation_categories`**
   - Kategori pelanggaran berhierarki (induk dan sub-kategori).
   - Kolom: `id`, `parent_id` (FK self-referencing, nullable), `code` (e.g. `P1`, `P1.1`), `name`, `group_type` (`terlambat`, `kehadiran`, `seragam`, `kerapian`, `kedisiplinan`), `severity` (`ringan`, `sedang`, `berat`, `sangat_berat`), `points` (base 8, 16, 32, 64), `is_zero_tolerance` (boolean), `is_recurring` (boolean), `recurring_increment` (nullable), `max_recurring_points` (nullable), `sanksi` (deskripsi sanksi dokumen), `description`, `status` (`draft`, `active`).
7. **`achievement_categories`**
   - Kategori prestasi dan penghargaan (10 Pilar Pancawaluya).
   - Kolom: `id`, `parent_id` (FK self-referencing, nullable), `code` (e.g. `R1`, `R1.1`), `name`, `group_type` (`akademik`, `karakter`, `sosial`, `keagamaan`, dll.), `points` (bobot reward), `status` (`draft`, `active`).
8. **`redemption_rules`**
   - Tabel padanan kanonik antara pelanggaran dengan sub-prestasi pemulihan.
   - Kolom: `id`, `violation_category_id` (FK), `achievement_category_id` (FK). Zero tolerance tidak memiliki baris di tabel ini.

### 3.3 Data Transaksional Operasional
9. **`discipline_cases`**
   - Berkas perkara kasus pelanggaran siswa.
   - Kolom: `id`, `case_number` (format `KAS-YYYY-XXXX`), `student_id` (FK), `enrollment_id` (FK snapshot), `class_id` (FK snapshot), `violation_category_id` (FK), `academic_year_id` (FK snapshot), `reporter_id` (FK `users`), `incident_date`, `incident_time`, `location`, `description`, `status` (`found`, `handling`, `closed`, `dismissed`), `validator_id` (FK `users`, saat handling), `validated_at`, `closer_id` (FK `users`, saat closed), `closed_at`, `points_override` (nullable).
10. **`achievement_records`**
    - Catatan pencapaian prestasi atau penyelesaian program penebusan.
    - Kolom: `id`, `student_id` (FK), `enrollment_id` (FK snapshot), `class_id` (FK snapshot), `achievement_category_id` (FK), `discipline_case_id` (FK nullable, tautan penebusan), `academic_year_id` (FK snapshot), `recorded_by` (FK `users`), `event_name`, `event_date`, `achievement_level`, `points_awarded`, `status` (`pending`, `verified`, `rejected`), `verified_by` (FK `users`), `verified_at`, `description`.

### 3.4 Data Audit Mutasi Saldo
11. **`point_ledgers`**
    - Audit trail mutasi poin yang bersifat append-only.
    - Kolom: `id`, `student_id` (FK), `academic_year_id` (FK), `direction` (`credit`, `debit`), `amount`, `balance_after`, `transaction_type` (`OPENING_BALANCE`, `VIOLATION`, `ACHIEVEMENT`, `RECOVERY`, `REVERSAL`), `source_type` (polymorphic class, e.g. `App\Models\DisciplineCase`), `source_id` (polymorphic ID), `reason`, `created_by` (FK `users`), `verified_by` (FK `users`), `verified_at` (timestamp nullable), `created_at`.
12. **`settings`**
    - Key-value configuration untuk identitas instansi sekolah dan legalitas sertifikat.
    - Kolom: `id`, `key`, `value`, `created_at`, `updated_at`.

---

## 4. Prinsip Perancangan Kunci (Key Architectural Invariants)

### 4.1 Enrollment sebagai Single Source of Truth
- Kolom `class_id` dan `academic_year_id` pada tabel `students` telah dihapus.
- Hubungan siswa terhadap kelas dan tingkat kelas (`X`, `XI`, `XII`) selalu diperoleh melalui relasi `enrollments`.
- Promosi kelas pada tahun ajaran baru tidak memutasi (rename) baris kelas lama, melainkan membentuk baris kelas baru dan baris pendaftaran (`enrollments`) baru. Kelas lama tetap ada sebagai arsip statis.

### 4.2 Snapshot Context Pattern pada Kasus dan Prestasi
Saat insiden pelanggaran atau pencapaian prestasi dicatat, sistem mengambil snapshot relasi akademik saat kejadian (`class_id`, `enrollment_id`, `academic_year_id`). 
- Tujuannya adalah memastikan bahwa jika siswa di masa mendatang naik kelas atau pindah kelas, riwayat kasus tetap mencatat kelas saat kejadian berlangsung, bukan kelas terbaru siswa.

### 4.3 Append-Only Ledger Immutability
- Tabel `point_ledgers` tidak mengizinkan operasi SQL `UPDATE` maupun `DELETE` pada tingkat aplikasi.
- Saldo siswa dihitung dari entri ledger terbaru (`balance_after` dari baris `id` tertinggi pada tahun ajaran bersangkutan).
- Jika terjadi kesalahan administratif, perbaikan dilakukan melalui penerbitan transaksi baru bertipe `REVERSAL` yang mengembalikan saldo secara transparan.
- Setiap operasi mutasi dilindungi transaksi database (`DB::transaction`) dan penguncian baris (`lockForUpdate`) untuk mencegah race condition pada akses konkurensi.
