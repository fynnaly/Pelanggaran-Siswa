# Dokumentasi REST API Pendamping Android (Pelanggaran Kuy)

## 1. Ikhtisar API
Backend Pelanggaran Siswa menyediakan antarmuka **RESTful API versi 1 (`/api/v1`)** yang didedikasikan untuk aplikasi pendamping Android (*Pelanggaran Kuy*, Kotlin + Jetpack Compose). Seluruh komunikasi data menggunakan format pertukaran JSON dengan otentikasi token *Bearer* melalui **Laravel Sanctum**.

- **Base URL Pengembangan**: `http://10.0.2.2:8000/api/v1/` (Emulator Android) atau IP LAN Host `http://192.168.x.x:8000/api/v1/`
- **Root Health Check**: `GET /api/v1/` (Public, tanpa otentikasi untuk verifikasi jabat tangan koneksi mobile).

---

## 2. Pengamanan dan Kebijakan Pembatasan (*Rate Limiting*)
1. **Otentikasi Bearer Token**: Setiap request yang dilindungi wajib menyertakan header HTTP:
   ```text
   Authorization: Bearer <sanctum_plain_text_token>
   Accept: application/json
   ```
2. **Throttle Rate Limiter**:
   - `throttle:api-login`: Maksimal 5 percobaan login per menit per kombinasi Email dan Alamat IP (pertahanan anti *brute-force*).
   - `throttle:api`: Maksimal 60 permintaan per menit per pengguna terotentikasi.
3. **Penyelarasan Hak Akses (RBAC Scope)**:
   - Wali kelas hanya diizinkan melihat saldo dan rekap data siswa di kelas yang diampunya.
   - Siswa hanya diizinkan mengambil data dan riwayat buku kas mutasi poin miliknya sendiri. Akses ID siswa lain menghasilkan respons `403 Forbidden`.

---

## 3. Spesifikasi Endpoint

### 3.1 Pengecekan Status API (Health Check)
Memastikan layanan backend aktif dan dapat dijangkau dari aplikasi seluler.

- **URL**: `GET /api/v1/`
- **Otentikasi**: Tidak diperlukan.
- **Contoh Respons (`200 OK`)**:
  ```json
  {
    "name": "Pelanggaran Kuy",
    "version": "v1",
    "endpoints": [
      "POST auth/login",
      "GET auth/me (auth:sanctum)",
      "POST auth/logout (auth:sanctum)",
      "PATCH auth/password (auth:sanctum)",
      "GET dashboard (auth:sanctum)",
      "GET profile (auth:sanctum)",
      "PATCH profile (auth:sanctum)",
      "GET monitor-poin (auth:sanctum)",
      "GET students/{id}/points (auth:sanctum)",
      "GET students/search (auth:sanctum)",
      "GET students/by-nfc/{uid} (auth:sanctum)",
      "GET violation-categories (auth:sanctum)",
      "GET discipline-cases (auth:sanctum)",
      "POST discipline-cases (auth:sanctum)",
      "GET achievement-categories (auth:sanctum)",
      "GET students/{student}/achievements (auth:sanctum)",
      "POST achievement-records (auth:sanctum)",
      "GET reports/students/{student} (auth:sanctum)",
      "GET reports/classes/{schoolClass} (auth:sanctum)"
    ]
  }
  ```

---

### 3.2 Otentikasi dan Manajemen Sesi

#### A. Masuk Sistem (Login)
- **URL**: `POST /api/v1/auth/login`
- **Header**: `Accept: application/json`
- **Payload Request**:
  ```json
  {
    "email": "guru1@guru.com",
    "password": "password",
    "device_name": "Samsung Galaxy A54"
  }
  ```
- **Respons Berhasil (`200 OK`)**:
  ```json
  {
    "token_type": "Bearer",
    "token": "1|abcdef1234567890...",
    "expires_in": 2592000,
    "user": {
      "id": 22,
      "name": "Guru Budi",
      "email": "guru1@guru.com",
      "nip": "198501012010011002",
      "roles": ["guru"],
      "is_active": true,
      "avatar_path": "avatars/budi.png",
      "avatar_url": "http://localhost:8000/storage/avatars/budi.png",
      "siswa": null
    }
  }
  ```
  Catatan: `siswa` berisi `{id, nisn, nama, kelas}` khusus akun peran `siswa`, selain itu `null`.
- **Respons Gagal (`422 Unprocessable Entity` / `401 Unauthorized`)**:
  ```json
  {
    "message": "Email atau password salah."
  }
  ```

#### B. Profil Pengguna Aktif (Me)
- **URL**: `GET /api/v1/auth/me`
- **Header**: `Authorization: Bearer <token>`
- **Respons Berhasil (`200 OK`)**:
  ```json
  {
    "user": {
      "id": 22,
      "name": "Guru Budi",
      "email": "guru1@guru.com",
      "nip": "198501012010011002",
      "roles": ["guru"],
      "is_active": true,
      "avatar_path": "avatars/budi.png",
      "avatar_url": "http://localhost:8000/storage/avatars/budi.png",
      "siswa": null
    }
  }
  ```

#### C. Ganti Password Sendiri
- **URL**: `PATCH /api/v1/auth/password`
- **Header**: `Authorization: Bearer <token>`, `Content-Type: application/json`
- **Hak Akses**: Semua peran kecuali `siswa` (akun siswa hanya-baca, `403`).
- **Payload Request**:
  ```json
  {
    "current_password": "password-lama",
    "password": "password-baru-min-8",
    "password_confirmation": "password-baru-min-8"
  }
  ```
- **Aturan**: Password lama wajib benar (`422` jika salah: `{"current_password": ["Password lama salah."]}`); password baru minimal 8 karakter dan harus cocok dengan konfirmasi. Token perangkat aktif **tidak dicabut** agar sesi di perangkat ini tetap berjalan.
- **Respons Berhasil (`200 OK`)**:
  ```json
  {
    "message": "Password berhasil diubah."
  }
  ```

#### D. Keluar Sistem (Logout)
- **URL**: `POST /api/v1/auth/logout`
- **Header**: `Authorization: Bearer <token>`
- **Respons Berhasil (`200 OK`)**:
  ```json
  {
    "message": "Token berhasil dicabut."
  }
  ```

---

### 3.3 Monitoring Poin dan Rekapitulasi Siswa

#### A. Daftar Monitoring Poin Siswa
Mengambil daftar siswa beserta saldo poin terkini dan klasifikasi 8 zona pembinaan.

- **URL**: `GET /api/v1/monitor-poin`
- **Header**: `Authorization: Bearer <token>`
- **Parameter Kueri (Opsional)**:
  - `q`: Pencarian nama siswa, NIS, atau NISN.
  - `class_id`: Filter ID rombel kelas tertentu.
  - `zone`: Filter nama zona (misal: `pembinaan-1`, `intervensi-khusus`).
  - `sort`: Kolom sortir (`name`, `points`).
  - `order`: Arah pengurutan (`asc`, `desc`).
- **Respons Berhasil (`200 OK`)**:
  ```json
  {
    "data": [
      {
        "id": 105,
        "full_name": "Ahmad Rizki",
        "nis": "242518001",
        "nisn": "0081234567",
        "class_name": "X PPLG 1",
        "balance": 1780,
        "zone": {
          "name": "Pembinaan 2",
          "color": "badge-warning",
          "responsible": "Wali Kelas + Guru BK",
          "action": "Kolaborasi pendampingan Wali Kelas dan Guru BK"
        }
      }
    ],
    "meta": {
      "total": 36,
      "current_page": 1,
      "last_page": 2
    }
  }
  ```

---

### 3.4 Detail Mutasi Poin Siswa (Ledger Audit)

#### A. Buku Kas Poin Siswa Tunggal
Mengambil rincian mutasi kredit dan debit yang pernah dialami siswa.

- **URL**: `GET /api/v1/students/{id}/points`
- **Header**: `Authorization: Bearer <token>`
- **Respons Berhasil (`200 OK`)**:
  ```json
  {
    "student": {
      "id": 105,
      "full_name": "Ahmad Rizki",
      "nis": "242518001",
      "class_name": "X PPLG 1",
      "current_balance": 1780,
      "zone": "Pembinaan 2"
    },
    "history": [
      {
        "id": 412,
        "date": "2026-10-25 07:15:00",
        "type": "VIOLATION",
        "direction": "debit",
        "amount": 16,
        "balance_after": 1780,
        "description": "Pelanggaran: P1.2 Lebih dari 10 menit (Kejadian ke-2)",
        "officer": "Guru BK Rahma"
      },
      {
        "id": 310,
        "date": "2026-10-20 10:00:00",
        "type": "ACHIEVEMENT",
        "direction": "credit",
        "amount": 20,
        "balance_after": 1796,
        "description": "Prestasi: R9.1 Merawat sarana kelas (Penebusan KAS-2026-0042)",
        "officer": "Guru BK Rahma"
      },
      {
        "id": 1,
        "date": "2026-07-15 08:00:00",
        "type": "OPENING_BALANCE",
        "direction": "credit",
        "amount": 2000,
        "balance_after": 2000,
        "description": "Saldo pembuka tahun ajaran 2026/2027",
        "officer": "Administrator"
      }
    ]
  }
  ```
- **Respons Gagal (`403 Forbidden`)**:
  Terjadi jika akun peran `siswa` mencoba mengakses data ID siswa lain, atau akun peran `guru` mencoba mengakses ID siswa di luar kelas perwaliannya.
  ```json
  {
    "message": "Anda tidak memiliki otorisasi untuk mengakses riwayat siswa ini."
  }
  ```

---

## 3.5 Pencarian Siswa & Lookup NFC

### A. Pencarian Siswa Cepat (Autocompletion)
Digunakan saat guru mengetik nama / NISN / NIS siswa di form input pelanggaran (minimum 2 karakter).

- **URL**: `GET /api/v1/students/search`
- **Header**: `Authorization: Bearer <token>`
- **Hak Akses**: Staf Sekolah (Guru, Wali Kelas, BK, Kesiswaan, Admin). Akun peran `siswa` ditolak `403 Forbidden`.
- **Parameter Kueri**:
- `q` (string, required): Kata kunci nama, NIS, atau NISN (min 2 karakter).
- `limit` (int, opsional, default: 10, max: 50).
- **Respons Berhasil (`200 OK`)**:
```json
{
  "data": [
    {
      "id": 15,
      "nis": "NIS-015",
      "nisn": "0012345678",
      "nama": "Budi Santoso",
      "kelas": "X RPL 1",
      "saldo_poin": 2000,
      "zona": "Normal"
    }
  ]
}
```

### B. Scan Nametag Siswa (NFC UID Lookup)
Digunakan saat perangkat membaca chip NFC pada kartu tanda pengenal siswa untuk melihat profil dan status saldo secara instan.

- **URL**: `GET /api/v1/students/by-nfc/{uid}`
- **Header**: `Authorization: Bearer <token>`
- **Hak Akses**: Semua pengguna terotentikasi (Guru, BK, Kesiswaan, Admin, Siswa).
- **Parameter URL**: `uid` (string, contoh: `04A23B891F6080`, pencarian case-insensitive).
- **Respons Berhasil (`200 OK`)**:
```json
{
  "siswa": {
    "id": 15,
    "nisn": "0012345678",
    "nis": "NIS-015",
    "nama": "Budi Santoso",
    "kelas": "X RPL 1"
  },
  "saldo": 2000,
  "zona": {
    "nama": "Normal",
    "warna": "hijau",
    "penanggung_jawab": "Wali Kelas",
    "tindakan": "Pembinaan rutin"
  }
}
```
- **Respons Tidak Ditemukan (`404 Not Found`)**:
```json
{
  "message": "Nametag belum terdaftar pada siswa mana pun."
}
```

---

## 3.6 Katalog Tata Tertib & Kategori Pelanggaran

### A. Daftar Kategori Pelanggaran
Digunakan untuk opsi dropdown pilihan jenis pelanggaran serta tampilan buku pedoman tata tertib di aplikasi Android.

- **URL**: `GET /api/v1/violation-categories`
- **Header**: `Authorization: Bearer <token>`
- **Hak Akses**: Semua pengguna terotentikasi (termasuk Siswa).
- **Parameter Kueri**:
- `grouped` (boolean, opsional, default: `true`): Jika `true`, dikelompokkan per kategori induk (P1-P5) dengan relasi `sub_categories`. Jika `false`, mengembalikan daftar datar (*flat*) sub-kategori.
- **Respons Berhasil (`200 OK` - Grouped)**:
```json
{
  "data": [
    {
      "id": 1,
      "code": "P1",
      "name": "Kerapian & Atribut",
      "sub_categories": [
        {
          "id": 101,
          "code": "P1.1",
          "name": "Tidak memakai dasi / ikat pinggang standar",
          "points": 5,
          "is_recurring": false
        }
      ]
    }
  ]
}
```
- **Respons Berhasil (`200 OK` - Flat `?grouped=false`)**:
```json
{
  "data": [
    {
      "id": 101,
      "code": "P1.1",
      "name": "Tidak memakai dasi / ikat pinggang standar",
      "points": 5,
      "is_recurring": false
    }
  ]
}
```

---

## 3.7 Kasus Pelanggaran Disiplin

### A. Pelaporan Pelanggaran Baru dari Lapangan
Mencatat temuan pelanggaran siswa secara langsung saat razia atau observasi guru di lingkungan sekolah.

- **URL**: `POST /api/v1/discipline-cases`
- **Header**:
- `Authorization: Bearer <token>`
- `Accept: application/json`
- `Content-Type: application/json`
- **Hak Akses**: Staf Sekolah (Guru, Wali Kelas, BK, Kesiswaan, Admin). Akun peran `siswa` ditolak `403 Forbidden`.
- **Payload Request**:
```json
{
  "student_id": 15,
  "violation_category_id": 101,
  "incident_date": "2026-11-12",
  "incident_time": "08:15",
  "location": "Gerbang Depan Sekolah",
  "notes": "Tidak memakai dasi saat upacara bendera hari Senin."
}
```
- **Aturan Validasi**:
- `student_id`: Wajib (`required`), harus ada di tabel `students`.
- `violation_category_id`: Wajib (`required`), harus ada di tabel `violation_categories`, wajib berupa sub-kategori aktif (bukan kategori induk).
- `incident_date`: Opsional, format `YYYY-MM-DD` (default: hari ini).
- `incident_time`: Opsional, format `H:i` (default: waktu server saat ini).
- `location`: Opsional, string max 100 karakter.
- `notes`: Wajib (`required`), string deskripsi kronologi temuan.
- **Respons Berhasil (`201 Created`)**:
```json
{
  "success": true,
  "message": "Laporan pelanggaran berhasil dicatat (status: Temuan).",
  "data": {
    "case_number": "KAS-2026-0042",
    "student_name": "Budi Santoso",
    "violation": "Tidak memakai dasi / ikat pinggang standar",
    "points": 5,
    "status": "found",
    "reporter": "Andi Pratama, S.Pd."
  }
}
```
- **Respons Validasi Gagal (`422 Unprocessable Entity`)**:
```json
{
  "message": "Keterangan / catatan pelanggaran wajib diisi.",
  "errors": {
    "notes": [
      "Keterangan / catatan pelanggaran wajib diisi."
    ]
  }
}
```

### B. Riwayat Kasus Pelanggaran
Mengambil daftar kasus pelanggaran yang pernah dilaporkan dengan filter status dan siswa.

- **URL**: `GET /api/v1/discipline-cases`
- **Header**: `Authorization: Bearer <token>`
- **Hak Akses**: Staf Sekolah (Guru, Wali Kelas, BK, Kesiswaan, Admin).
- **Parameter Kueri (Opsional)**:
- `status`: Filter status kasus (`found`, `handling`, `closed`, `dismissed`).
- `student_id`: Filter ID siswa tertentu.
- `limit`: Jumlah data per halaman (default: 15, max: 100).
- `page`: Nomor halaman paginasi.
- **Respons Berhasil (`200 OK`)**:
```json
{
  "data": [
    {
      "id": 42,
      "case_number": "KAS-2026-0042",
      "student_name": "Budi Santoso",
      "class_name": "X RPL 1",
      "violation_name": "Tidak memakai dasi / ikat pinggang standar",
      "points": 5,
      "status": "found",
      "incident_date": "2026-11-12",
      "created_at": "2026-11-12T08:20:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "total": 1
  }
}
```

---

## 3.8 Dashboard Beranda (Ringkasan per Peran)

Satu endpoint untuk layar utama aplikasi mobile: staf mendapat statistik sekolah, siswa mendapat rekap poin pribadinya.

- **URL**: `GET /api/v1/dashboard`
- **Header**: `Authorization: Bearer <token>`
- **Hak Akses**: Semua peran terdaftar. Scope wali kelas tetap berlaku untuk staf.

#### A. Respons untuk Staf (`role: "staf"`)
```json
{
  "role": "staf",
  "scope": "semua",
  "tahun_ajaran_aktif": "2026",
  "stats": {
    "kasus_aktif": 5,
    "kasus_bulan_ini": 6,
    "prestasi_bulan_ini": 1,
    "total_siswa": 83
  },
  "kasus_terbaru": [
    {
      "case_number": "KAS-2026-0006",
      "student_name": "Siswa 1",
      "class_name": "X RPL 1",
      "violation_name": "Membuang sampah sembarangan dan merusak lingkungan.",
      "points": 32,
      "status": "handling",
      "incident_date": "2026-11-14"
    }
  ]
}
```
- `scope`: `semua` (admin/BK/kesiswaan/kepala sekolah) atau `wali_kelas` (hanya kelas yang diampu).
- Statistik dihitung pada tahun ajaran aktif; `kasus_aktif` menghitung kasus yang belum `closed/dismissed`; angka "bulan ini" mengikuti bulan kalender berjalan berdasarkan tanggal pencatatan.

#### B. Respons untuk Siswa (`role: "siswa"`)
```json
{
  "role": "siswa",
  "siswa": {
    "id": 1,
    "nama": "Siswa 1",
    "nis": "NIS-001",
    "nisn": "001",
    "kelas": "X RPL 1"
  },
  "stats": {
    "poin": 1916,
    "zona": "Normal",
    "penanggung_jawab": "Wali Kelas",
    "tindakan": "Pemeliharaan budaya disiplin harian",
    "kasus": 5,
    "prestasi": 1
  },
  "kasus_terbaru": [ ... ],
  "prestasi_terbaru": [
    {
      "event_name": "Tidak melanggar selama 3bulan.",
      "category_name": "Tidak pernah melanggar tata tertib minimal 3 bulan berturut-turut",
      "points": 20,
      "status": "verified",
      "event_date": "2026-11-14"
    }
  ]
}
```
- Respons `403` jika akun siswa belum terhubung dengan data siswa.

---

## 3.9 Profil Pribadi dan Avatar

#### A. Baca Profil
- **URL**: `GET /api/v1/profile`
- **Header**: `Authorization: Bearer <token>`
- **Respons Berhasil (`200 OK`)**: bentuk sama dengan `auth/me` (field `user` memuat `name`, `email`, `nip`, `roles`, `avatar_path`, `avatar_url`, dan `student` dengan `id/nis/nisn/name/class`). `avatar_url` selalu berisi URL — memakai gambar avatar default bila pengguna belum mengunggah foto.

#### B. Ubah Profil dan Avatar
- **URL**: `PATCH /api/v1/profile`
- **Header**: `Authorization: Bearer <token>`, `Content-Type: multipart/form-data` (bila ada berkas avatar)
- **Hak Akses**: Staf saja. Akun `siswa` bersifat hanya-baca — seluruh perubahan (termasuk avatar) ditolak dengan `403`.
- **Payload (multipart/form-data)**:
  - `name`, `email`, `nip`: opsional (staf saja).
  - `avatar`: berkas gambar JPG/JPEG/PNG/WebP maks. 50MB.
  - `remove_avatar`: `true` untuk menghapus foto profil (avatar lama ikut dihapus dari storage).
- **Perilaku**: mengganti avatar otomatis menghapus berkas lama; mengubah email mereset `email_verified_at`.
- **Respons Berhasil (`200 OK`)**:
  ```json
  {
    "message": "Profil berhasil diperbarui.",
    "user": { "...": "bentuk sama dengan GET profile" }
  }
  ```

---

## 3.10 Katalog Prestasi dan Pencatatan Penebusan

#### A. Daftar Kategori Prestasi Aktif
- **URL**: `GET /api/v1/achievement-categories`
- **Header**: `Authorization: Bearer <token>`
- **Hak Akses**: Semua pengguna terdaftar.
- **Respons Berhasil (`200 OK`)**:
```json
{
  "data": [
    {
      "id": 27,
      "parent_id": 4,
      "parent_name": "Kedisiplinan",
      "code": "R4.2",
      "name": "Tidak pernah melanggar tata tertib minimal 3 bulan berturut-turut",
      "points": 20,
      "description": "..."
    }
  ]
}
```
- Hanya sub-kategori berstatus `active` dan berada dalam masa berlaku (`effective_date`/`expiry_date`).

#### B. Riwayat Prestasi Siswa
- **URL**: `GET /api/v1/students/{student}/achievements`
- **Header**: `Authorization: Bearer <token>`
- **Hak Akses**: Siswa hanya boleh melihat miliknya sendiri; wali kelas terbatas pada kelas ampunya; staf lain bebas.
- **Parameter Kueri**: `status` (opsional: `pending`/`verified`/`rejected`), `limit` (default 20, maks 100), `page`.
- **Respons Berhasil (`200 OK`)**:
```json
{
  "data": [
    {
      "id": 1,
      "student_id": 1,
      "event_name": "Tidak melanggar selama 3bulan.",
      "event_date": "2026-11-14",
      "status": "verified",
      "points_awarded": 20,
      "category": { "id": 27, "code": "R4.2", "name": "...", "parent_name": "Kedisiplinan" },
      "discipline_case": { "id": 4, "case_number": "KAS-2026-0004" },
      "class": "X RPL 1",
      "academic_year": "2026",
      "recorded_by": "Umar Faruq",
      "verified_by": "Umar Faruq",
      "verified_at": "2026-11-14T01:20:29.000000Z",
      "evidence_path": null
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "total": 1 }
}
```

#### C. Catat Prestasi Baru (Termasuk Penebusan Kasus)
- **URL**: `POST /api/v1/achievement-records`
- **Header**: `Authorization: Bearer <token>`, `Content-Type: application/json`
- **Hak Akses**: Staf sekolah. Role `bk`/`kesiswaan` otomatis memverifikasi (`verified` + kredit poin); role lain menghasilkan `pending`.
- **Payload Request**:
```json
{
  "student_id": 1,
  "achievement_category_id": 27,
  "discipline_case_id": 4,
  "event_name": "Tidak melanggar selama 3 bulan",
  "event_date": "2026-11-14",
  "description": "Siswa tidak pernah melanggar tata tertib 3 bulan berturut-turut"
}
```
- **Aturan Validasi**:
  - `achievement_category_id` wajib sub-kategori aktif.
  - `discipline_case_id` opsional: kasus harus milik siswa tersebut, belum `closed/dismissed/archived`, belum tertaut prestasi lain, dan kategorinya harus dipadankan (atau kategori universal) dengan kategori pelanggarannya.
- **Respons Berhasil (`201 Created`)**:
```json
{
  "message": "Rekam prestasi berhasil dicatat dengan status pending.",
  "data": { "...": "bentuk sama dengan riwayat prestasi" }
}
```

---

## 3.11 Laporan Riwayat Siswa dan Kelas

#### A. Riwayat Siswa (JSON)
- **URL**: `GET /api/v1/reports/students/{student}`
- **Header**: `Authorization: Bearer <token>`
- **Hak Akses**: Pemilik (siswa) atau staf; wali kelas terbatas pada kelas binaan.
- **Parameter Kueri (Opsional)**: `year` (tanpa param = tahun ajaran aktif; `year=` kosong = semua tahun), `class`, `from`, `to`.
- **Aturan periode**: kasus dihitung dari `incident_date` (fallback `created_at`), prestasi dari `event_date` (fallback `created_at`).
- **Respons Berhasil (`200 OK`)**:
```json
{
  "data": {
    "students": [
      {
        "student": { "id": 1, "nis": "NIS-001", "nisn": "001", "name": "Siswa 1", "status": "active" },
        "cases": [
          {
            "id": 6,
            "case_number": "KAS-2026-0006",
            "incident_date": "2026-11-14",
            "category": "Membuang sampah sembarangan dan merusak lingkungan.",
            "category_parent": "Ketertiban & Kedisiplinan Umum",
            "status": "handling",
            "description": "...",
            "class": "X RPL 1",
            "academic_year": "2026",
            "reported_by": "Guru 1",
            "validated_by": "Umar Faruq"
          }
        ],
        "achievements": [ { "event_name": "...", "verified_by": "...", "evidence_url": null } ],
        "point_ledger": [
          {
            "id": 6,
            "date": "2026-11-14T01:20:29.000000Z",
            "direction": "debit",
            "amount": 32,
            "balance_after": 1916,
            "transaction_type": "VIOLATION",
            "reason": "Pelanggaran: P5.10",
            "source": { "type": "discipline_case", "id": 6, "case_number": "KAS-2026-0006" }
          }
        ]
      }
    ]
  },
  "filters": { "year": 1, "class": null, "from": null, "to": null }
}
```

#### B. Riwayat Kelas (JSON)
- **URL**: `GET /api/v1/reports/classes/{schoolClass}`
- **Header**: `Authorization: Bearer <token>`
- **Hak Akses**: Staf sekolah; wali kelas hanya kelas binaannya.
- **Parameter Kueri**: sama dengan laporan siswa (hasil dikelompokkan per siswa snapshot kelas tersebut).
- **Respons Berhasil (`200 OK`)**: bentuk sama dengan laporan siswa, plus:
```json
{
  "class": { "id": 1, "name": "X RPL 1", "academic_year": "2026" }
}
```
