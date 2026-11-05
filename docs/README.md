# Dokumentasi Resmi Sistem Informasi Pelanggaran & Tata Tertib Siswa

Selamat datang di repositori dokumentasi resmi sistem **Pelanggaran Siswa (Pelanggaran Kuy)**. Seluruh perancangan, arsitektur teknis, panduan operasional, dan spesifikasi antarmuka diorganisir ke dalam berkas-berkas terpisah di bawah ini:

---

## Indeks Dokumentasi Terstruktur

| Berkas | Deskripsi Isi Dokumen | Sasaran Pembaca |
| :--- | :--- | :--- |
| [**erd.md**](erd.md) | **Entity Relationship Diagram (ERD)**: Diagram grafis Mermaid relasi 12 entitas database, kardinalitas 1:N dan polymorphic, serta kamus relasi bahasa manusia. | Pengembang, DB Designer, Penguji |
| [**database.md**](database.md) | **Spesifikasi Teknis Basis Data**: Kamus data tabel lengkap, tipe data, nullability, unique constraint, index performa, dan jaminan integritas audit. | Database Administrator, Backend Dev |
| [**usecase.md**](usecase.md) | **Use Case Diagram & Spesifikasi Fungsional**: Diagram Use Case Mermaid 6 aktor, skenario langkah operasional (UC-01 s.d. UC-05), dan matriks hak akses CRUD. | Product Owner, Analis Sistem, Penguji |
| [**flowchart.md**](flowchart.md) | **Diagram Alir Proses Bisnis (Flowcharts)**: Diagram alir alur kasus 3 status, transaksi atomik point ledger, penebusan restoratif, promosi kelas, dan cetak sertifikat. | Semua Pihak, Pengembang, Penguji |
| [**latar-belakang-dan-masalah.md**](latar-belakang-dan-masalah.md) | **For What, Masalah, dan Kenapa**: Latar belakang pembinaan restoratif, identifikasi 5 masalah tata tertib konvensional, alasan pemilihan model saldo awal 2000, 8 zona skor, dan hierarki Pancawaluya. | Semua pihak, Penguji, Pembina |
| [**perancangan.md**](perancangan.md) | **Perancangan Sistem & Basis Data**: Matriks RBAC 6 peran, struktur 12 entitas tabel database relasional, skema *Enrollment as Single Source of Truth*, dan aturan integritas audit ledger. | Pengembang, Database Administrator |
| [**arsitektur.md**](arsitektur.md) | **Arsitektur Perangkat Lunak & Alur Data**: Diagram arsitektur modular MVC, struktur direktori kode, mesin status siklus kasus (*found $\rightarrow$ handling $\rightarrow$ closed*), atomisitas Point Ledger, dan pipeline CI/CD GitHub Actions. | Software Engineer, DevOps |
| [**tema-dan-desain.md**](tema-dan-desain.md) | **Desain Antarmuka & Standar Visual**: Filosofi visual *quiet governance*, token warna CSS, komponen kartu `.card-form`, kebijakan ketat bebas emoji, dukungan Dark Mode, dan mitigasi peramban Force Dark. | Frontend Engineer, UI/UX Designer |
| [**fitur.md**](fitur.md) | **Katalog Spesifikasi Fitur Lengkap**: Rincian 8 modul aplikasi (Akun, Referensi Akademik, Hierarki Pelanggaran Skala 8/16/32/64, Prestasi & Penebusan, Siklus Kasus, Buku Kas Poin, dan Pengaturan Sistem). | Product Owner, QA Tester |
| [**panduan-penggunaan.md**](panduan-penggunaan.md) | **SOP & Panduan Penggunaan Sistem**: Langkah demi langkah operasional sistem berdasarkan peran kerja (Administrator, Guru / Wali Kelas, Guru BK, Tim Kesiswaan, Kepala Sekolah, dan Siswa). | Pengguna Akhir, Staf Sekolah, Siswa |
| [**api-companion.md**](api-companion.md) | **Spesifikasi REST API Android Companion**: Dokumentasi endpoint `/api/v1` untuk aplikasi seluler *Pelanggaran Kuy* (Auth Sanctum, Rate Limiter, Monitoring Poin, dan Audit Ledger). | Mobile App Developer (Android) |

---

## Ringkasan Spesifikasi Teknis Proyek

- **Kerangka Kerja Backend**: Laravel 13.x (PHP 8.3+)
- **Basis Data**: MariaDB / MySQL 8.x
- **Manajemen Hak Akses**: Spatie Laravel-Permission (6 Peran Aktif: `admin`, `guru`, `bk`, `kesiswaan`, `kepala_sekolah`, `siswa`)
- **Antarmuka Pengguna**: Laravel Blade Templates + Token-Driven CSS + Alpine.js
- **Aplikasi Pendamping Mobile**: Kotlin + Jetpack Compose (API `/api/v1`)
- **Pustaka Ikon**: Lucide Icons CDN (SVG-based, 0 decorative emoji)
- **Model Akuntansi Disiplin**: Append-Only Point Ledger, Saldo Pembuka 2000 Poin, Skala Penalti 8 / 16 / 32 / 64, dan 8 Zona Skor Pembinaan Karakter.
