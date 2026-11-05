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
      "GET monitor-poin (auth:sanctum)",
      "GET students/{id}/points (auth:sanctum)"
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
    "token": "1|abcdef1234567890...",
    "user": {
      "id": 22,
      "name": "Guru Budi",
      "email": "guru1@guru.com",
      "roles": ["guru"]
    }
  }
  ```
- **Respons Gagal (`422 Unprocessable Entity` / `401 Unauthorized`)**:
  ```json
  {
    "message": "Kredensial yang diberikan tidak cocok dengan data kami."
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
      "avatar_url": "http://localhost:8000/storage/avatars/budi.png",
      "nip": "198501012010011002",
      "roles": ["guru"],
      "homeroom_class": "X PPLG 1"
    }
  }
  ```

#### C. Keluar Sistem (Logout)
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
