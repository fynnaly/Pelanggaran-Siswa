# Dokumentasi Projek — Sistem Poin Pelanggaran & Prestasi Siswa

> Status: Ujikom (adaptasi dari Perancangan Induk v1.0)
> Metode: SDLC Waterfall (diadaptasi iteratif per task Kanban)
> Repo: GitHub Private — Board: Kanban "Pelanggaran Siswa"

---

## 1. Riwayat Perubahan (Changelog Perancangan)

| Aspek | Rancangan Induk v1.0 | Keputusan Ujikom v2.0 | Alasan |
|---|---|---|---|
| NFC Nametag | Wajib | **DIHAPUS total** | Keputusan pengguna; tidak akan diimplementasikan |
| Platform | Android + Web | **Web saja** | Scope Ujikom |
| Database | Supabase | **PostgreSQL lokal (XAMPP/pgAdmin4)** | Environment Ujikom |
| Status kasus | 8 status | **3 status: found → validated → done** (+ dismissed) | Simplifikasi demo |
| Role | 7 role | **4 role: admin, guru, bk, siswa** *(saran, menunggu Q&A)* | Simplifikasi |
| Saldo awal | 2.000 | **2.000 (dipertahankan)** | Fondasi dokumen |
| Batas saldo | Zona F0–F4 | **Min 0, maks tak terbatas (2.500+)** | Keputusan pengguna |
| Leaderboard | Tidak ada | **DITAMBAH: Top 10, username, opt-out** | Ide pengguna (privasi) |
| Kustomisasi profil | Tidak ada | **DITAMBAH: username & avatar** | Ide pengguna (privasi) |
| Recovery | recovery_plans kompleks | **Kredit manual beralasan (RECOVERY)** | Simplifikasi |
| Export | maatwebsite/excel | **CSV native** | (bug) PHP 8.5.5 tidak kompatibel |
| Matriks pelanggaran | A/B vs P1–P5 (konflik) | **3 tingkat draft: -10/-30/-80** | Sekolah belum tetapkan poin |

---

## 2. Keputusan Terkunci

1. Saldo pembuka **2.000**, dibuat **satu kali** per siswa per tahun ajaran.
2. Saldo **minimal 0**, **tanpa batas atas**.
3. Pelanggaran status `found` **tidak mengubah saldo**. Saldo berubah hanya saat `validated`.
4. Prestasi menambah saldo hanya setelah `verified`.
5. Ledger **append-only** — koreksi via transaksi `REVERSAL`, bukan edit/hapus.
6. **Pelapor tidak dapat memvalidasi laporannya sendiri.**
7. Leaderboard menampilkan **username**, bukan nama lengkap. Siswa bisa **opt-out**.
8. Nama lengkap siswa **tidak dapat diubah** oleh siswa.
9. Wali kelas = atribut **kelas per tahun ajaran** (bukan atribut siswa).

### Menunggu Q&A (belum terkunci)
- Basis leaderboard: poin prestasi *(saran)* vs saldo bersih.
- Final role: 3 vs 4.
- Benefit/reward poin.
- Matriks final: 3 tingkat draft vs matriks A/B sekolah.

---

## 3. Tech Stack

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 12 + Breeze (Blade + Tailwind) |
| Auth & Role | Laravel Breeze + Spatie Permission |
| Database | PostgreSQL (lokal), dicek via pgAdmin4 |
| Export | CSV native (tanpa library) |
| Tool | Composer, Git + GitHub Projects (Kanban) |

> (bug) Tercatat: `maatwebsite/excel` gagal di PHP 8.5.5 (butuh PHP <8.5 / ext-gd).
> Keputusan: gunakan CSV native.

---

## 4. Role & Hak Akses

| Role | Hak utama |
|---|---|
| admin | Master data, akun, validasi, konfigurasi, lihat semua |
| guru | Catat pelanggaran (`found`), usul prestasi |
| bk | Validasi pelanggaran/prestasi, kredit recovery, lihat siswa |
| siswa | Lihat saldo/riwayat/prestasi sendiri, ubah username & avatar, lihat leaderboard |

---

## 5. Model Poin

```
saldo = 2.000 (OPENING_BALANCE)
      + kredit (ACHIEVEMENT, RECOVERY)
      - debit  (VIOLATION)
minimum = 0 | maksimum = tak terbatas
```

| transaction_type | Arah | Pemicu |
|---|---|---|
| OPENING_BALANCE | +2.000 | Aktivasi tahun ajaran |
| ACHIEVEMENT | + | Prestasi `verified` |
| VIOLATION | - | Pelanggaran `validated` |
| RECOVERY | + | Kredit manual beralasan (maks s.d. 2.000) *(saran)* |
| REVERSAL | ± | Koreksi resmi |

---

## 6. Alur & Status

**Pelanggaran:** `found` (guru catat) → `validated` (BK/admin setujui, debit masuk) → `done` | atau `dismissed` (tidak terbukti, tanpa debit).

**Prestasi:** `pending` (guru usul) → `verified` (kredit masuk) | `rejected`.

---

## 7. Skema Database (8 tabel inti)

### users (Breeze + Spatie)
`id, name, email, password, timestamps` + role Spatie.

### academic_years
`id, name (2026/2027), start_date, end_date, is_active, timestamps`

### classes
`id, academic_year_id FK, name (X-1), homeroom_teacher_id FK users nullable, timestamps`
> Wali kelas melekat pada kelas per tahun ajaran → ganti wali = update baris kelas tahun baru.

### students
`id, user_id FK nullable, class_id FK, nisn, full_name, username unique nullable, avatar_path nullable, is_leaderboard_visible boolean default true, status, timestamps`

### violation_categories
`id, code, name, severity (ringan/sedang/berat), points int, status (draft/active), timestamps`

### achievement_categories
`id, code, name, points int, status, timestamps`

### discipline_cases
`id, case_number unique, student_id FK, violation_category_id FK, reported_by FK users, status (found/validated/dismissed/done), location, description, validated_by FK nullable, validated_at nullable, timestamps`

### achievement_records
`id, student_id FK, achievement_category_id FK, recorded_by FK, status (pending/verified/rejected), description, verified_by nullable, timestamps`

### point_ledgers
`id, student_id FK, academic_year_id FK, direction (credit/debit), amount, balance_after, transaction_type, source_type nullable, source_id nullable, reason nullable, created_by FK, verified_by FK nullable, timestamps`
> Constraint: satu OPENING_BALANCE per student+academic_year (cek di service + partial unique index *(saran)*).

### Relasi
- academic_years hasMany classes
- classes hasMany students; belongsTo users (homeroom_teacher)
- students hasMany discipline_cases, achievement_records, point_ledgers
- violation_categories hasMany discipline_cases
- discipline_cases → point_ledgers (source)

---

## 8. Backlog Kanban (sesuai papan)

**Done:** #2 Laravel+Starter Kit · #3 Spatie Role · #5 Middleware+Policy · #6 PostgreSQL Test
**In Progress:** #4 Migrasi & Seeder Data Awal (Dummy)
**Backlog:** #8 Opening Balance · #9 Master Pelanggaran · #10 Master Prestasi · #11 Catat Pelanggaran · #12 Validasi Pelanggaran · #13 Prestasi · #14 Profil Siswa · #15 Dashboard Siswa · #16 Leaderboard · #17 Dashboard Staf · #18 Export CSV · #19 Policy anti-validasi-sendiri · #20 UAT · #7 Supabase Produksi (opsional)

---

## 9. Setup

```bash
composer create-project laravel/laravel pelanggaran-siswa
composer require laravel/breeze --dev && php artisan breeze:install blade
composer require spatie/laravel-permission
# .env → DB_CONNECTION=pgsql, sesuaikan database
php artisan migrate && php artisan db:seed
php artisan serve
```

Cek data: pgAdmin4 → Databases → (nama DB) → Schemas → public → Tables → klik kanan → View/Edit Data.

---

## 10. UAT Ringkas (kriteria lolos Ujikom)

- [ ] Siswa baru tepat satu transaksi +2.000 (import ulang tidak menduplikasi).
- [ ] Pelanggaran `found` → saldo belum berubah.
- [ ] `validated` → debit masuk sekali; `dismissed` → tanpa debit.
- [ ] Prestasi `verified` → kredit masuk sekali.
- [ ] Guru validasi laporan sendiri → **ditolak**.
- [ ] Siswa ubah username → tampil di leaderboard; opt-out → hilang.
- [ ] Saldo tidak pernah di bawah 0.

---

*Dokumen ini hidup — perbarui setiap ada keputusan baru dari sesi Q&A.*
