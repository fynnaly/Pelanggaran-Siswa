# Spesifikasi Fitur dan Modul Fungsional

Dokumen ini menguraikan seluruh modul fungsional yang tersedia pada aplikasi Pelanggaran Siswa, batasan akses (RBAC), aturan bisnis, dan integrasi antar-komponen.

---

## 1. Modul Autentikasi dan Manajemen Akun Pengguna

### 1.1 Autentikasi Terproteksi
- **Login Multi-Peran**: Masuk menggunakan alamat email dan kata sandi yang di-hash dengan algoritma Bcrypt standar industri.
- **Pencegahan Riwayat Mundur (`PreventBackHistory`)**: Header HTTP anti-caching memastikan bahwa setelah sesi pengguna berakhir (*logout*), tombol *Back* pada peramban tidak dapat menampilkan kembali data sensitif siswa di layar.
- **Proteksi Akses Siswa**: Peran siswa hanya diizinkan membaca data pribadi mereka sendiri dan diblokir dari seluruh menu operasional serta formulir pelaporan staf.

### 1.2 Profil Pengguna dan Identitas Kepegawaian
- **Pengaturan Profil Berbasis Tab**: Tampilan terbagi menjadi kategori *Informasi Akun* dan *Zona Bahaya*.
- **Foto Profil Pegawai (Avatar)**: Staf dapat mengunggah foto profil (JPG/PNG/WebP maks. 2MB) yang tersimpan aman pada storage publik lokal (`public/storage/avatars`).
- **Nomor Induk Pegawai (NIP)**: Staf dapat melengkapi NIP resmi yang akan dicantumkan secara otomatis pada dokumen penanganan kasus dan berkas rekapitulasi.
- **Deteksi Tugas Tambahan**: Sistem secara cerdas mendeteksi dan menampilkan lencana tugas struktural guru, seperti status *Wali Kelas X PPLG 1* berdasarkan tahun ajaran yang sedang aktif.

### 1.3 Pengamanan Zona Bahaya (Penghapusan Akun)
- **Soft Deletes Eloquent**: Akun pengguna yang dihapus tidak langsung dihilangkan dari tabel basis data secara permanen, melainkan diberi stempel waktu `deleted_at`. Seluruh riwayat audit (kasus yang pernah dilaporkan atau divalidasi) tetap utuh.
- **Friction Konfirmasi Nama (GitHub Pattern)**: Modal konfirmasi mewajibkan pengguna mengetikkan nama lengkapnya secara persis ke dalam kolom input sebelum tombol konfirmasi aktif (`:disabled`).

---

## 2. Modul Data Referensi Akademik

### 2.1 Tahun Ajaran Aktif (`AcademicYear`)
- Menyimpan nama periode (contoh: `2026/2027`), tanggal mulai, status operasional, dan penanda status aktif (`is_active`).
- **Pemicu Kenaikan Kelas Otomatis (`PromotionService`)**: Pengaktifan tahun ajaran baru secara otomatis menyalin struktur kelas ke tahun ajaran baru, memperbarui pendaftaran siswa (*enrollments*), menandai siswa tingkat XII sebagai lulus, dan membawa saldo poin berjalan sebagai saldo awal tahun baru.

### 2.2 Rombongan Belajar / Kelas (`SchoolClass`)
- Menyimpan data nama rombel (contoh: `X PPLG 1`), tingkat kelas (`X`, `XI`, `XII`), dan Program Keahlian (`major`, e.g. `PPLG`, `DKV`).
- Mengaitkan guru sebagai **Wali Kelas** (`homeroom_teacher_id`) untuk keperluan pendampingan siswa di Zona Pembinaan.
- Mendukung penetapan guru wali kelas secara cepat langsung dari baris tabel data (*inline quick-assign*).

### 2.3 Manajemen Profil Siswa (`Student`)
- Berisi identitas statis siswa: Nama Lengkap, NIS, NISN, Jenis Kelamin, Nama dan Nomor Telepon Orang Tua, serta Alamat.
- **Independen dari Kelas**: Siswa tidak lagi menyimpan `class_id` langsung di tabel `students`. Seluruh data kelas siswa berjalan melalui riwayat pendaftaran aktif (`enrollments`).

### 2.4 Impor dan Ekspor Data Siswa Berkinerja Tinggi
- **Impor CSV Native**: Mengunggah berkas CSV/XLSX tanpa ketergantungan library eksternal yang berat, memvalidasi keunikan NISN/NIS, dan otomatis mendaftarkan siswa ke kelas serta membuka saldo poin awal 2000.
- **Ekspor XLSX Terproteksi**: Mendukung pengunduhan data siswa, pengguna, dan monitor poin berformat XLSX ber-style template native dengan proteksi *Formula Injection*.

---

## 3. Modul Kategori Pelanggaran (Pedoman Pancawaluya Tabel 2)

### 3.1 Struktur Hierarki Berjenjang
- Kategori pelanggaran terbagi menjadi kategori induk (P1 sampai P5) dan 53 sub-pelanggaran spesifik dengan kode dokumen resmi:
  - **P1**: Pelanggaran Terlambat (P1.1 - P1.3)
  - **P2**: Pelanggaran Kehadiran & Presensi (P2.1 - P2.11)
  - **P3**: Pelanggaran Pakaian & Atribut Seragam (P3.1 - P3.14)
  - **P4**: Pelanggaran Kerapian & Penampilan (P4.1 - P4.9)
  - **P5**: Pelanggaran Ketertiban & Kedisiplinan Umum (P5.1 - P5.16)

### 3.2 Skala Poin Baru: 8 / 16 / 32 / 64
- **Ringan (8 Poin)**: Pelanggaran etika dasar, seragam harian, dan keterlambatan minor.
- **Sedang (16 Poin)**: Izin/sakit tanpa keterangan, atribut tidak lengkap, pelanggaran gawai.
- **Berat (32 Poin)**: Perusakan sarana sekolah (vandalisme), pembuangan sampah sembarangan berulang, pengambilan barang orang lain.
- **Sangat Berat / Zero Tolerance (64 Poin)**: Pelanggaran hukum dan norma berat (narkoba, senjata tajam, perjudian, pornografi, bullying, perkelahian massal).

### 3.3 Eskalasi Otomatis Toleransi 3 Tingkat
- Bagi pelanggaran yang berulang, sistem menerapkan perhitungan eskalasi otomatis:
  $$\text{Tingkat I} \rightarrow \text{Tingkat II} \rightarrow \text{Tingkat III}$$
  $$(base \rightarrow base \times 2 \rightarrow base \times 4 \quad \text{dengan batas atas plafon 32})$$
- Contoh pada pelanggaran terlambat minor (8 poin):
  - Kejadian ke-1: -8 poin (Teguran lisan).
  - Kejadian ke-2: -16 poin (Teguran tertulis dan penugasan).
  - Kejadian ke-3 dst: -32 poin (Pemanggilan orang tua).
- Kasus berulang dihitung berdasarkan riwayat pelanggaran sebelumnya yang telah diproses (*handling/closed*).

### 3.4 Kebijakan Zero Tolerance
- Kategori dengan bobot 64 poin ditandai `is_zero_tolerance = true`.
- Nilai poin flat pada 64 (tidak bertambah karena sudah merupakan sanksi tertinggi).
- **Tidak memiliki jalur penebusan via prestasi**: Siswa wajib mengikuti Program Pembinaan Intensif dan Rapat Dewan Guru.

---

## 4. Modul Kategori Prestasi dan Aturan Penebusan Poin

### 4.1 Sepuluh Pilar Penghargaan Pancawaluya (Tabel 1)
- Master data prestasi mencakup 51 sub-kategori yang terbagi ke dalam 10 pilar utama (R1 sampai R10):
  - **R1**: Prestasi Religi & Ketaatan Beribadah
  - **R2**: Prestasi Kejujuran & Integritas Karakter
  - **R3**: Prestasi Kedisiplinan & Budaya Industri
  - **R4**: Prestasi Kerapian & Kesantunan Diri
  - **R5**: Prestasi Sosial & Kepedulian Lingkungan
  - **R6**: Prestasi Kepemimpinan & Organisasi
  - **R7**: Prestasi Kebangsaan & Bela Negara
  - **R8**: Prestasi Literasi & Pengembangan Diri
  - **R9**: Prestasi Pemeliharaan Sarana & Lingkungan Sekolah
  - **R10**: Prestasi Kejuaraan & Kompetisi Akademik/Kejuruan

### 4.2 Aturan Penebusan Poin Kanonik (`redemption_rules`)
- Menghubungkan satu jenis pelanggaran dengan sub-prestasi wajib yang harus diraih siswa sebagai wujud pemulihan perilaku.
- Sistem secara otomatis menampilkan saran sub-prestasi yang dapat diambil saat melihat detail kasus pelanggaran.

---

## 5. Modul Pelaporan dan Siklus Kasus Pelanggaran

### 5.1 Siklus Hidup Kasus 3 Tahap
1. **Tahap Temuan (`found`)**:
   - Seluruh laporan awal staf masuk berstatus `found`.
   - **Saldo poin siswa belum dipotong**. Hal ini memberi ruang klarifikasi agar siswa tidak langsung dirugikan jika terjadi kesalahpahaman atau kesalahan input.
   - Kasus dapat ditolak (*dismissed*) jika laporan tidak terbukti.
2. **Tahap Penanganan (`handling`)**:
   - Dijalankan oleh Guru BK atau Koordinator Kesiswaan.
   - Poin pelanggaran otomatis dipotong dari saldo buku kas siswa (`PointLedger DEBIT`).
   - Rencana pembinaan dan jadwal bimbingan ditetapkan.
3. **Tahap Selesai (`closed`)**:
   - Ditutup setelah siswa menyelesaikan program pembinaan atau kewajiban sanksi.
   - Berkas perkara dikunci sebagai rekam jejak historis sekolah.

### 5.2 Pengisian Kelas Otomatis
- Form pelaporan kasus secara otomatis mendeteksi rombel aktif siswa dari tabel `enrollments`. Staf tidak perlu lagi menebak atau memilih kelas secara manual dari dropdown.

### 5.3 Pelaporan Siswa Kolektif (Bulk Input)
- **Pencarian Multi-Siswa**: Mengetik dan menambahkan banyak siswa dalam satu laporan insiden yang sama.
- **Pilihan Cepat Satu Kelas**: Opsi memilih satu rombel kelas (misal: "X PPLG 1") untuk memasukkan seluruh siswa aktif di rombel tersebut ke dalam berkas laporan secara instan.
- **Tombol Kosongkan Semua**: Mereset daftar siswa terpilih dalam 1 klik jika staf salah memilih rombel.

### 5.4 Pelapor Otomatis dan Terkunci
- Kolom pelapor otomatis membaca identitas akun staf yang sedang membuka form dan terkunci dalam status *disabled* (`cursor-not-allowed`). Staf tidak dapat memalsukan nama pelapor atas nama orang lain.

---

## 6. Modul Pencatatan Prestasi Siswa

### 6.1 Pencatatan Prestasi Tunggal dan Rombel
- Mendukung apresiasi prestasi individual (misal: Juara LKS) maupun penghargaan kolektif untuk seluruh siswa satu kelas (misal: Juara Kebersihan Kelas).

### 6.2 Integrasi Penebusan Kasus Terbuka
- Saat mencatat prestasi untuk seorang siswa, form secara cerdas memeriksa apakah siswa tersebut memiliki kasus pelanggaran yang masih aktif (*open cases*).
- Jika ada, form menyediakan opsi untuk menautkan prestasi ini sebagai penebusan berkas perkara tersebut.

### 6.3 Verifikasi dan Penambahan Poin
- Status awal prestasi adalah `pending`.
- Begitu Guru BK atau Kesiswaan memverifikasi prestasi (`verified`), poin kredit langsung dicatatkan ke dalam `point_ledgers`, memulihkan saldo kedisiplinan siswa secara akurat.

---

## 7. Modul Buku Kas Poin (Point Ledger) dan 8 Zona Skor

### 7.1 Buku Kas Poin Append-Only
- Seluruh mutasi saldo dicatat secara kronologis tanpa izin hapus atau ubah.
- Saldo awal tahun ajaran diberikan otomatis sebesar 2000 poin.
- Perhitungan saldo menggunakan query sub-agregasi ID tertinggi yang terbebas dari masalah performa $N+1$.

### 7.2 Delapan Zona Skor Pembinaan
| Zona Skor | Rentang Saldo | Penanggung Jawab | Tindakan dan Konsekuensi |
| :--- | :---: | :--- | :--- |
| **Istimewa** | $> 2500$ | Kepala Sekolah | Sertifikat *Anugerah Genep Waluya Utama* |
| **Luar Biasa** | $2200 - 2499$ | Kepala Sekolah | Sertifikat *Anugerah Genep Waluya* |
| **Normal** | $1900 - 2199$ | Wali Kelas | Pemeliharaan budaya disiplin harian |
| **Pembinaan 1** | $1800 - 1899$ | Wali Kelas | Pendampingan dan konseling intensif wali kelas |
| **Pembinaan 2** | $1700 - 1799$ | Wali Kelas & BK | Kolaborasi bimbingan konseling dan pemetaan masalah |
| **Pembinaan 3** | $1600 - 1699$ | Sekolah & Orang Tua | Pemanggilan resmi orang tua/wali ke sekolah |
| **Pembinaan 4** | $1500 - 1599$ | Tim Kesiswaan | Surat Peringatan (SP) dan penanganan langsung kesiswaan |
| **Intervensi Khusus** | $< 1500$ | Kepala Sekolah | Sidang Dewan Guru, pertimbangan kelanjutan studi |

---

## 8. Modul Pengaturan Sistem dan Legalitas Dokumen

### 8.1 Pengaturan Sistem Dua Mode (Read-Only Default)
- Menghindari modifikasi konfigurasi sekolah yang tidak disengaja. Halaman berstatus *Read-only* saat pertama kali dibuka.
- Menekan tombol **Ubah Pengaturan** membuka akses sunting; menekan **Batal** otomatis mereset form kembali ke kondisi semula.
- Tombol simpan dilindungi dialog konfirmasi native.

### 8.2 Konfigurasi Identitas dan Penandatanganan Digital
- Pengaturan nama website aplikasi, nama sekolah, nomor telepon resmi, dan alamat instansi.
- Upload berkas logo sekolah beresolusi tinggi.
- Upload tanda tangan digital (PNG transparan) serta penulisan nama lengkap dan NIP pejabat:
  1. Kepala Sekolah
  2. Koordinator Kesiswaan

### 8.3 Cetak Sertifikat dan Lembar Kasus
- Template cetak dokumen legalitas formal siap cetak langsung melalui fungsi peramban (`window.print()`).
- Tata letak otomatis menyesuaikan margin lembar resmi dengan kop dinas pendidikan, logo sekolah, nomor registrasi surat, dan kolom tanda tangan ganda.
