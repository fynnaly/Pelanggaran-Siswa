# Backend → Frontend Integration Audit — Pelanggaran Siswa

> **For Hermes:** Use subagent-driven-development skill to implement plan task-by-task.
> **Branch:** `feat/crud-optimization` | **Date:** 2026-09-18 | **Mode:** audit gap backend vs frontend
> **Stack:** Laravel 12, Breeze, Spatie Permission, PostgreSQL 18, Blade + Tailwind, paginate 20

## Ringkasan Eksekutif

Backend punya 8 model, 8 controller, 6 resource routes aktif. Frontend punya views untuk 6 domain. **2 domain 0% terintegrasi** (Achievement), **1 bug binding kritis** (DisciplineCase show), **1 bug nav wildcard**, plus beberapa partial/UX debt. Achievement = gap terbesar — model+migration+stub controller ada tapi tanpa route/view/policy sama sekali.

---

## 1. Matriks Integrasi (Backend vs Frontend)

| Domain | Model | Migration | Controller | Routes `web.php` | Views (`resources/views`) | Nav Link | Status |
|---|---|---|---|---|---|---|---|
| **AcademicYear** | ✅ | ✅ | ✅ CRUD penuh | ✅ `resource` | ✅ index/create/edit | ✅ | **DONE** (diff ada di branch — optimasi tampilan) |
| **ViolationCategory** | ✅ | ✅ | ✅ CRUD penuh | ✅ `resource` | ✅ index/create/edit | ✅ | **DONE** |
| **Student** | ✅ | ✅ | ✅ +search/export/import/template | ✅ `resource`+extra | ✅ index/create/edit | ✅ | **DONE** (search duplikat, lihat §3) |
| **SchoolClass (classes)** | ✅ | ✅ | ✅ CRUD | ✅ `resource` | ✅ index/create/edit | ✅ | **DONE** (edit wali brittle) |
| **DisciplineCase** | ✅ | ✅ | ✅ index/create/store/show+validate/done | ⚠️ `only(index,create,store,show)` | ✅ index/create/show | ✅ | **BUG** §2.1 param mismatch |
| **PointLedger** | ✅ | ✅ (verified_at type bug) | ✅ index/show only | ✅ `only(index,show)` | ✅ index/show | ✅ | **PARTIAL** append-only OK, filter+reversal missing |
| **AchievementCategory** | ✅ | ✅ | ❌ STUB kosong | ❌ no route | ❌ no view folder | ❌ | **0% — BELUM INTEGRASI** |
| **AchievementRecord** | ✅ | ✅ | ❌ STUB kosong | ❌ no route | ❌ no view folder | ❌ | **0% — BELUM INTEGRASI** |
| **Dashboard** | — | — | ✅ `DashboardController@index` | ✅ `/dashboard` | ✅ `dashboard.blade.php` | ✅ | **PARTIAL** stats incomplete |
| **User/Profile** | ✅ | ✅ | ✅ Breeze | ✅ `auth.php` | ✅ `profile/*` | dropdown | **DONE** (admin user CRUD missing) |
| **Welcome `/`** | — | — | closure | ✅ | ✅ `welcome.blade.php` | — | **STUB** default Laravel |

---

## 2. Bug Kritis — Integrasi Putus (Fix Dulu)

### 2.1 DisciplineCase `show` Route-Model Binding Mismatch
- **Routes:** `Route::resource(...)->only(['index','create','store','show'])` → param `{disciplineCase}`
- **Controller:** `show(DisciplineCase $case)` — param name `$case` ≠ `{disciplineCase}`
- **Dampak:** Laravel implicit binding gagal → 404 / Missing `DisciplineCase` di `/discipline-cases/{id}`
- **View:** `show.blade.php` pakai `$case` (sinkron dengan controller tapi tidak dengan route)
- **Fix:** Ubah signature jadi `show(DisciplineCase $disciplineCase)` + compact `disciplineCase`, atau custom `parameters()` di route. Update `show.blade.php`, `index.blade.php` `route('discipline-cases.show', $case)` tetap jalan karena model binding, tapi variabel di view harus konsisten. **Pilih A:** rename controller param → `disciplineCase`.

### 2.2 Navigation `routeIs` Wildcard Hilang → Active State Mati
- **File:** `resources/views/layouts/navigation.blade.php:18,21,24,27,30,33,89,92,95,98,101,104`
- **Sekarang:** `request()->routeIs('students')` — hanya match route bernama `students` (tidak ada)
- **Harusnya:** `request()->routeIs('students.*')`, `classes.*`, `violation-categories.*`, dst.
- **Dampak:** Link tidak pernah highlight active kecuali `dashboard` (exact match)
- **Fix:** Tambah `.*` di semua `routeIs`.

### 2.3 Balance After Precedence Bug
- **File:** `DisciplineCaseController::validate` ~L118
- **Sekarang:** `$disciplineCase->student->pointLedgers()->latest('id')->value('balance_after') ?? 2000 - $points` → `??` binding lebih rendah dari `-`, jadi `2000 - $points` dievaluasi dulu tapi `value()` bisa null → fallback jadi `2000 - points` (kebetulan benar tapi rapuh). Seharusnya `(latest()->value(...) ?? 2000) - $points`. Plus concurrency: tanpa `lockForUpdate` + transaksi.
- **Fix:** Wrap + DB::transaction + `lockForUpdate`.

---

## 3. Partial / Belum Selesai (Frontend Ada Tapi Kurang)

### 3.1 Dashboard Tidak Lengkap
- **Controller:** `DashboardController` sudah kirim `totalCasesDismissed`, `recentCases` eager `student,violationCategory,reporter` (N+1 fixed di branch).
- **View:** `dashboard.blade.php` hanya render 4 kartu + recentCases, belum pakai `totalCasesDismissed`/`totalCasesDone` breakdown, belum ada filter tahun ajaran, chart, atau siswa poin rendah.
- **Task:** Tambah section breakdown status, low-point students (balance <= 500), kartu `Tahun Ajaran Aktif`.

### 3.2 PointLedger — Filter & Reversal Missing
- **Sekarang:** `index` = `latest()->paginate(20)` tanpa filter student/tahun/direction/type/q. `show` ada.
- **Alasan ledger append-only:** benar (REVERSAL via entri baru), tapi **tidak ada UI** untuk buat REVERSAL.
- **Task:** Tambah filter query (siswa, tahun ajaran, direction, type) + tombol/form `Buat Reversal` (credit, amount, reason, academic_year) → `store` baru khusus reversal dengan policy `bk|admin`.

### 3.3 Student Search Duplikat
- **Routes:** `GET students/search → search()` + `GET students?q=` di `index()` (branch optimasi sudah tambah `q` di index)
- **Dampak:** Dua endpoint search, frontend hanya pakai `?q=` (form di `index.blade.php`). `/search` jadi dead route atau untuk AJAX autocomplete (create.blade.php punya student picker?).
- **Task:** Putuskan: **keep `index?q` untuk list, `search` JSON untuk autocomplete** di `discipline-cases/create` (student picker). Dokumentasikan, jangan hapus.

### 3.4 SchoolClass Edit — Wali Kelas Query Rapuh
- **File:** `resources/views/classes/edit.blade.php` query inline `whereHas('roles', fn => where('name','admin'))` + fallback `whereHas('roles', where('name',''))` → kondisi kedua aneh, plus query di view (bukan controller).
- **Fix:** Pindah query ke `SchoolClassController@edit` (`$users = User::role('admin|bk|guru')` atau `orderBy name`), view hanya render `$users`.

### 3.5 Classes Index N+1
- **File:** `classes/index.blade.php: $class->students()->count()` di loop → query per row padahal controller sudah `with('students')`. Harusnya `$class->students_count` via `withCount`.
- **Fix:** Controller `withCount('students')`, view pakai `students_count`.

### 3.6 Verified_at Column Type Mismatch (Known)
- **Migration `000008_create_point_ledgers_table`:** `verified_at` salah jadi `foreignId()->constrained('users')`.
- **Status:** Branch sudah `git diff` hapus migration fix `2026_09_15_132001_fix_point_ledgers_verified_column.php` dan patch migration awal. Butuh `fresh` atau `migrate:fresh` decision. Plan jangan ubah lagi di task ini — catat sebagai tech debt jika belum `migrate:fresh` di staging.

---

## 4. Belum Integrasi Sama Sekali (0%)

### 4.1 AchievementCategory — Full CRUD Hilang
- **Ada:** Model `AchievementCategory` (code,name,points,status draft|active), migration `000005`, controller stub kosong.
- **Belum:** Routes, Policy, Views (index/create/edit), Nav link, Seeder.
- **Expected (mirror ViolationCategory):**
  - `Route::resource('achievement-categories', AchievementCategoryController::class);`
  - Controller: index paginate20, create/store, edit/update, destroy (guard jika punya records)
  - Views: `resources/views/achievement-categories/{index,create,edit}.blade.php`
  - Nav: `Prestasi` (atau `Kategori Prestasi`)

### 4.2 AchievementRecord — Full Flow + Ledger Credit Hilang
- **Ada:** Model `AchievementRecord` (student_id, achievement_category_id, recorded_by, status, description, verified_by/at), migration `000007`, controller stub, `PointLedger` constants `TYPE_ACHIEVEMENT`, `DIR_CREDIT`.
- **Belum:** Routes, Views, Controller logic, Policy, Ledger credit on validate, Nav.
- **Expected flow (mirror DisciplineCase):** `found|validated → dismissed|done` atau minimal `pending → verified` → buat ledger `credit` (tambah poin) + `balance_after`.
  - `Route::resource('achievement-records', AchievementRecordController::class)->only(['index','create','store','show']);`
  - `PATCH /achievement-records/{record}/validate` (bk|admin) → status `validated` + ledger `ACHIEVEMENT credit`
  - `PATCH /achievement-records/{record}/done`
  - Views: `resources/views/achievement-records/{index,create,show}.blade.php`
  - Picker siswa & kategori prestasi di `create` (reuse pattern `discipline-cases/create`)
  - Nav: `Prestasi` / `Rekam Prestasi`
- **Policy:** `AchievementRecordPolicy` (create guru|admin, validate bk|admin).

### 4.3 Admin User/Roles UI Hilang
- **Ada:** Spatie `permissions` + migration, seeder role `admin|bk|guru`, `User` model.
- **Belum:** CRUD user + assign role — tidak ada route/view sama sekali. Sekarang hanya `profile/edit`.
- **Task (low prio, buat plan fase 2):** `Route::resource('users', UserController::class)` + views. Tidak masuk MVP tapi catat.

### 4.4 Welcome & Test Glass Theme Tidak Terintegrasi
- `welcome.blade.php` masih default, `/test` standalone tanpa `x-app-layout`. Tidak blocking tapi branding sekolah hilang.

---

## 5. Checklist Verifikasi Frontend (Manual)

- [ ] Semua `routeIs` pakai `.*` → nav highlight benar
- [ ] `/discipline-cases/{id}` tidak 404
- [ ] Validasi kasus → ledger debit terbuat, balance_after benar (race-free)
- [ ] Dashboard angka cocok dengan DB
- [ ] Student import/export/template jalan (PHP native, tanpa ext-gd)
- [ ] Kelas create/edit unique per academic_year jalan
- [ ] PointLedger filter & reversal

---

## 6. Plan Eksekusi — Urut Prioritas (Jangan Paralel Semua)

### Task 1 — Fix Binding & Nav (30 menit) — P0 BUG
**Goal:** Show disiplin & nav active jalan.
**Files:** `DisciplineCaseController.php:83`, `resources/views/discipline-cases/show.blade.php`, `resources/views/layouts/navigation.blade.php`
**Steps:**
1. `patch` controller `show(DisciplineCase $disciplineCase)` + `return view(..., compact('disciplineCase'))` → rename `$case` → `$disciplineCase` di `show.blade.php` (atau keep `$case` alias tapi konsisten).
2. `patch` navigation semua `routeIs('x')` → `routeIs('x.*')` + `routeIs('point-ledgers.*')` etc.
3. `php artisan route:list | grep discipline` + `npm run build`
4. **Verify:** buka `/discipline-cases/1` tidak 404, klik nav tiap menu highlight.

### Task 2 — Ledger Balance Fix + Transaction (P0 BUG)
**Files:** `DisciplineCaseController.php:validate()`
**Steps:**
1. `DB::transaction` + `lockForUpdate` untuk ambil `balance_after` terakhir.
2. `$lastBalance = PointLedger::where('student_id', ...)->lockForUpdate()->latest('id')->value('balance_after') ?? 2000; $balanceAfter = $lastBalance - $points;`
3. Simpan ledger dengan `balance_after = $balanceAfter`.

### Task 3 — AchievementCategory CRUD (P1 FEATURE, 0% → 100%)
**Files:** new `app/Http/Controllers/AchievementCategoryController.php` (isi penuh), `routes/web.php`, `resources/views/achievement-categories/*`, `app/Policies/AchievementCategoryPolicy.php` (opsional)
**Steps:**
1. Implement controller mirror `ViolationCategoryController` (code unique, name, points, status draft|active, paginate 20).
2. Tambah `Route::resource('achievement-categories', ...)` di dalam `auth` group.
3. Buat views `index/create/edit.blade.php` (copy dari violation-categories, ganti route/model).
4. Tambah nav link `Prestasi` / `Kategori Prestasi`.
5. `npm run build`, `php artisan route:list`.

### Task 4 — AchievementRecord Full Flow (P1 FEATURE, 0% → 100%)
**Files:** `AchievementRecordController.php`, `AchievementRecordPolicy`, `routes/web.php`, `resources/views/achievement-records/*`, `PointLedger` credit
**Steps:**
1. Controller: index (filter status), create (pluck kategori active + student picker), store (case_number ACH-...), show, validate (status→validated + ledger credit), done (validated→done).
2. Routes: `resource only + patch validate/done`.
3. Views: `index` (filter tabs), `create` (student autocomplete via `students/search` JSON), `show` (detail + aksi validate/done).
4. Policy + `authorize`.
5. Nav link `Rekam Prestasi`.
**Depends:** Task 3.

### Task 5 — Dashboard Lengkap (P2 POLISH)
**Files:** `DashboardController.php`, `dashboard.blade.php`
**Steps:** Tambah `totalCasesDismissed`, `totalCasesDone` cards, `lowPointStudents` (balance <= 500), `activeAcademicYear`, chart mini jika ada.

### Task 6 — PointLedger Filter + Reversal UI (P2 POLISH)
**Files:** `PointLedgerController.php`, `point-ledgers/index.blade.php`, `point-ledgers/create-reversal.blade.php` atau modal
**Steps:** Query filter (student_id, academic_year_id, direction, transaction_type, q), reversal `store` (REVERSAL credit/debit).

### Task 7 — SchoolClass & Classes Index Polish (P2 FIX N+1)
**Files:** `SchoolClassController@index` (+withCount), `classes/index.blade.php`, `SchoolClassController@edit` + `classes/edit.blade.php`
**Steps:** `withCount('students')`, pindah query users ke controller.

### Task 8 — Docs & Cleanup (P3)
- Hapus/keep decision `students/search` vs `index?q` (doc).
- `welcome.blade.php` branding atau redirect `/` → `/dashboard` jika auth.
- `npm run build` final, `php -l` syntax check.

---

## 7. Estimasi

| Task | Effort | Risk |
|---|---|---|
| 1 Fix binding+nav | S | low |
| 2 Balance tx | S | med (concurrency) |
| 3 AchievementCategory | M | low (copy pattern) |
| 4 AchievementRecord | L | med (ledger credit) |
| 5 Dashboard | S | low |
| 6 Ledger filter+reversal | M | low |
| 7 Class polish | S | low |
| 8 Docs | S | low |

**Rekomendasi eksekusi:** 1→2→3→4 (inti), 5→6→7 bisa paralel setelah 4.

---

## 8. Catatan Arsitektur

- Flow pelanggaran: `found → validated|dismissed → done`. Mirror untuk prestasi: `found/pending → validated → done` (credit poin).
- Ledger append-only `REVERSAL` — jangan tambah edit/delete ledger.
- Paginate 20 konsisten.
- Text-only UI (no emoji policy Ghif).
- No private paths di commit/issue body (repo public).

---

## 9. Referensi File (Untuk Agent)

- `routes/web.php:16-45` — semua route aktif
- `app/Http/Controllers/{AcademicYear,Student,SchoolClass,ViolationCategory,DisciplineCase,PointLedger}Controller.php`
- `app/Models/{Student,DisciplineCase,PointLedger,AchievementCategory,AchievementRecord}.php`
- `resources/views/{academic-years,violation-categories,students,classes,discipline-cases,point-ledgers,dashboard}.blade.php`
- `resources/views/layouts/navigation.blade.php` — bug routeIs
