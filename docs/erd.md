# Entity Relationship Diagram (ERD) - Sistem Pelanggaran Siswa

Dokumen ini memodelkan seluruh entitas basis data relasional pada sistem **Pelanggaran Siswa (Pelanggaran Kuy)** beserta kardinalitas relasi antar-tabel.

---

## 1. Diagram ERD Lengkap (Mermaid)

```mermaid
erDiagram
    USERS ||--o{ STUDENTS : "memiliki akun login (1:1/1:0)"
    USERS ||--o{ CLASSES : "wali kelas (1:N)"
    USERS ||--o{ DISCIPLINE_CASES : "pelapor kasus (1:N)"
    USERS ||--o{ DISCIPLINE_CASES : "verifikator handling (1:N)"
    USERS ||--o{ ACHIEVEMENT_RECORDS : "pencatat prestasi (1:N)"
    USERS ||--o{ ACHIEVEMENT_RECORDS : "verifikator prestasi (1:N)"
    USERS ||--o{ POINT_LEDGERS : "operator pembuat (1:N)"

    ACADEMIC_YEARS ||--o{ CLASSES : "menaungi rombel (1:N)"
    ACADEMIC_YEARS ||--o{ ENROLLMENTS : "periode aktif siswa (1:N)"
    ACADEMIC_YEARS ||--o{ POINT_LEDGERS : "buku kas periode (1:N)"

    CLASSES ||--o{ ENROLLMENTS : "menampung siswa (1:N)"
    CLASSES ||--o{ DISCIPLINE_CASES : "snapshot kelas kasus (1:N)"

    STUDENTS ||--o{ ENROLLMENTS : "riwayat pendaftaran (1:N)"
    STUDENTS ||--o{ DISCIPLINE_CASES : "melakukan pelanggaran (1:N)"
    STUDENTS ||--o{ ACHIEVEMENT_RECORDS : "meraih prestasi (1:N)"
    STUDENTS ||--o{ POINT_LEDGERS : "memiliki mutasi saldo (1:N)"

    VIOLATION_CATEGORIES ||--o{ VIOLATION_CATEGORIES : "induk - sub kategori (1:N)"
    VIOLATION_CATEGORIES ||--o{ DISCIPLINE_CASES : "dasar aturan kasus (1:N)"
    VIOLATION_CATEGORIES ||--o{ REDEMPTION_RULES : "aturan penebusan (1:N)"

    ACHIEVEMENT_CATEGORIES ||--o{ ACHIEVEMENT_CATEGORIES : "induk - sub kategori (1:N)"
    ACHIEVEMENT_CATEGORIES ||--o{ ACHIEVEMENT_RECORDS : "kategori capaian (1:N)"
    ACHIEVEMENT_CATEGORIES ||--o{ REDEMPTION_RULES : "sarana pemulihan (1:N)"

    DISCIPLINE_CASES ||--o{ ACHIEVEMENT_RECORDS : "ditebus oleh (1:N)"
    DISCIPLINE_CASES ||--o{ POINT_LEDGERS : "sumber mutasi debit (polymorphic 1:1)"
    ACHIEVEMENT_RECORDS ||--o{ POINT_LEDGERS : "sumber mutasi kredit (polymorphic 1:1)"

    SETTINGS {
        string key PK
        text value
    }

    USERS {
        bigint id PK
        string name
        string email UK
        string nip
        string avatar_path
        timestamp email_verified_at
        string password
        timestamp deleted_at
    }

    ACADEMIC_YEARS {
        bigint id PK
        string name
        date start_date
        date end_date
        string status
        boolean is_active
        boolean is_locked
    }

    CLASSES {
        bigint id PK
        bigint academic_year_id FK
        string name
        string grade_level
        string major
        integer capacity
        bigint homeroom_teacher_id FK
        bigint assistant_teacher_id FK
    }

    STUDENTS {
        bigint id PK
        bigint user_id FK
        string nisn UK
        string nis UK
        string full_name
        string gender
        date birth_date
        string phone
        string parent_name
        string parent_phone
        text address
        string avatar_path
        string status
    }

    ENROLLMENTS {
        bigint id PK
        bigint student_id FK
        bigint class_id FK
        bigint academic_year_id FK
        string status
        date enrolled_at
        date ended_at
    }

    VIOLATION_CATEGORIES {
        bigint id PK
        bigint parent_id FK
        string code UK
        string name
        string severity
        integer points
        string group_type
        boolean is_recurring
        integer recurring_increment
        integer max_recurring_points
        string sanksi
        string status
    }

    ACHIEVEMENT_CATEGORIES {
        bigint id PK
        bigint parent_id FK
        string code UK
        string name
        integer points
        string group_type
        string status
    }

    REDEMPTION_RULES {
        bigint id PK
        bigint violation_category_id FK
        bigint achievement_category_id FK
    }

    DISCIPLINE_CASES {
        bigint id PK
        string case_number UK
        bigint student_id FK
        bigint violation_category_id FK
        bigint enrollment_id FK
        bigint class_id FK
        bigint academic_year_id FK
        bigint report_by FK
        string status
        date incident_date
        time incident_time
        string location
        text description
        bigint validated_by FK
        timestamp validated_at
    }

    ACHIEVEMENT_RECORDS {
        bigint id PK
        bigint student_id FK
        bigint achievement_category_id FK
        bigint discipline_case_id FK
        bigint enrollment_id FK
        bigint class_id FK
        bigint academic_year_id FK
        bigint recorded_by FK
        string event_name
        date event_date
        string achievement_level
        integer points_awarded
        string status
        bigint verified_by FK
        timestamp verified_at
        text description
    }

    POINT_LEDGERS {
        bigint id PK
        bigint student_id FK
        bigint academic_year_id FK
        string direction
        integer amount
        integer balance_after
        string transaction_type
        string source_type
        bigint source_id
        string reason
        bigint created_by FK
        bigint verified_by FK
        timestamp verified_at
    }
```

---

## 2. Penjelasan Kardinalitas Relasi (Bahasa Manusia)

1. **`users` ke `students` (1 ke 0..1)**:
   - Satu baris siswa dapat ditautkan ke satu akun user untuk login aplikasi mobile / web siswa. Jika belum memiliki akun, `user_id` bernilai `NULL`.
2. **`academic_years` ke `classes` (1 ke N)**:
   - Setiap rombongan belajar (kelas) terikat persis ke satu tahun ajaran. Saat tahun ajaran berganti, kelas baru dibentuk untuk menjaga arsip historis.
3. **`students` ke `enrollments` ke `classes` (Many-to-Many melalui `enrollments`)**:
   - `enrollments` adalah tabel penghubung (*pivot with history*). Satu siswa dapat memiliki banyak riwayat pendaftaran kelas (kelas X, XI, XII), namun hanya **1 pendaftaran yang berstatus `active`** pada tahun ajaran aktif.
4. **`violation_categories` (Self-Referencing 1 ke N)**:
   - Kategori induk (misal: *P1 Seragam*) memiliki banyak sub-kategori turunan (misal: *P1.1 Tidak Memakai Dasi*).
5. **`violation_categories` dan `achievement_categories` ke `redemption_rules` (Many-to-Many)**:
   - Menghubungkan pelanggaran tertentu dengan sub-prestasi wajib yang dapat memulihkan poin (aturan penebusan restoratif).
6. **`discipline_cases` ke `point_ledgers` (Polymorphic 1 ke 1)**:
   - Saat status kasus pelanggaran dinaikkan dari `found` menjadi `handling`, sistem menerbitkan **tepat 1 baris mutasi DEBIT** pada `point_ledgers` dengan referensi `source_type = DisciplineCase` dan `source_id = discipline_cases.id`.
7. **`achievement_records` ke `point_ledgers` (Polymorphic 1 ke 1)**:
   - Saat rekam prestasi diverifikasi (`verified`), sistem menerbitkan **tepat 1 baris mutasi KREDIT** pada `point_ledgers` dengan referensi `source_type = AchievementRecord` dan `source_id = achievement_records.id`.
