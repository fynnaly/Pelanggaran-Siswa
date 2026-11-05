# Panduan Penggunaan dan Standar Operasional Prosedur (SOP)

Buku panduan ini disusun sebagai rujukan teknis operasional bagi seluruh pemangku kepentingan di sekolah dalam mengoperasikan aplikasi Pelanggaran Siswa sesuai dengan peran dan tanggung jawabnya.

---

## 1. Panduan Administrator Sistem

### 1.1 Konfigurasi Awal Sekolah dan Identitas Surat
1. Masuk ke menu **Sistem $\rightarrow$ Pengaturan**.
2. Pada kondisi awal, halaman berada pada mode **Read-only**.
3. Klik tombol **Ubah Pengaturan** di kanan atas antarmuka.
4. Isi data identitas instansi:
   - **Nama Aplikasi**: Nama sistem yang tampil pada tab peramban.
   - **Nama Sekolah**: Nama resmi institusi pendidikan kejuruan.
   - **Alamat & Telepon**: Alamat fisik dan kontak telepon resmi sekolah.
   - **Logo Sekolah**: Unggah gambar logo berformat PNG/JPG.
5. Lengkapi data **Kop Dokumen & Penandatangan**:
   - Baris Pemerintah (contoh: *Pemerintah Daerah Provinsi Jawa Barat*).
   - Baris Dinas (contoh: *Dinas Pendidikan Cabang Wilayah VII*).
   - Tanda Tangan Kepala Sekolah & Kesiswaan (unggah berkas PNG transparan).
   - Masukkan Nama Lengkap beserta Gelar dan NIP resmi.
6. Klik tombol **Simpan Pengaturan** dan setujui dialog konfirmasi.

### 1.2 Manajemen Tahun Ajaran dan Promosi Kelas Otomatis
1. Masuk ke menu **Data Referensi $\rightarrow$ Tahun Ajaran**.
2. Untuk membuka tahun ajaran baru:
   - Klik **Tambah Tahun Ajaran**.
   - Masukkan nama periode (contoh: `2026/2027`) dan tanggal mulai.
   - Centang opsi **Aktifkan Tahun Ajaran Ini**.
3. **Konsekuensi Sistem**:
   - Pengaktifan tahun ajaran baru akan memicu mesin promosi otomatis (`PromotionService`).
   - Seluruh rombel kelas disalin secara otomatis ke tahun baru (tingkat X naik ke XI, XI naik ke XII).
   - Siswa kelas XII pada tahun sebelumnya otomatis ditandai berstatus **Lulus** (*graduated*).
   - Saldo poin berjalan siswa aktif dipindahkan (*carry forward*) menjadi saldo pembuka tahun baru.

### 1.3 Manajemen Pengguna dan Akun Staf
1. Masuk ke menu **Sistem $\rightarrow$ Manajemen Akun**.
2. Klik **Tambah Pengguna** untuk mendaftarkan staf baru.
3. Masukkan nama lengkap, email resmi, NIP, kata sandi, dan tentukan peran (*roles*):
   - Pilih `guru` untuk wali kelas atau staf pengajar.
   - Pilih `bk` untuk guru bimbingan konseling.
   - Pilih `kesiswaan` untuk tim tata tertib / kesiswaan.

---

## 2. Panduan Guru dan Wali Kelas

### 2.1 Pencatatan Temuan Pelanggaran di Lapangan
1. Masuk ke menu **Pelanggaran $\rightarrow$ Kasus Pelanggaran**.
2. Klik tombol **Buat Kasus Baru**.
3. **Pilih Siswa Pelanggar**:
   - *Kasus Siswa Tunggal*: Ketik nama atau NIS pada kolom pencarian, lalu klik nama siswa yang muncul pada hasil pencarian.
   - *Kasus Kelompok / Rombongan*: Cari dan pilih kembali siswa kedua, ketiga, dst. Seluruh siswa yang terpilih akan tampil dalam bentuk lencana (*badge*).
   - *Kasus Satu Kelas*: Gunakan dropdown **"Atau tambah satu kelas"** dan pilih nama rombel (misal: *X PPLG 1*). Seluruh siswa aktif di kelas tersebut akan otomatis terpilih.
   - *Batal Pilihan*: Klik ikon silang pada nama siswa untuk membatalkan satu orang, atau klik **Kosongkan Semua** jika salah memilih rombel.
4. **Waktu & Kategori**:
   - Tentukan tanggal kejadian (default terisi hari ini).
   - Kolom kelas otomatis mendeteksi rombel aktif siswa.
   - Pilih **Kategori Utama** (contoh: *P1 Terlambat*).
   - Pilih **Sub Kategori** (contoh: *P1.1 Kurang dari 10 menit*). Panel otomatis akan menampilkan bobot poin dan sanksi terkait.
5. **Kolom Pelapor**:
   - Kolom pelapor otomatis terkunci atas nama Anda sendiri.
6. **Keterangan**:
   - Tuliskan kronologi singkat insiden di kolom keterangan.
7. Klik **Simpan Kasus**. Status laporan tercatat sebagai **Temuan (`found`)** dan **saldo poin siswa belum dipotong**.

### 2.2 Pencatatan Apresiasi Prestasi Siswa
1. Masuk ke menu **Prestasi $\rightarrow$ Rekam Prestasi**.
2. Klik **Tambah Rekam Prestasi**.
3. Pilih siswa yang berprestasi (mendukung pilihan individu maupun penghargaan satu rombel penuh).
4. Pilih **Kategori Prestasi** dari daftar 10 Pilar Pancawaluya.
5. Isi nama event/kegiatan, tanggal pelaksanaan, dan tingkat kejuaraan (misal: *Tingkat Kota*).
6. Klik **Simpan Rekam Prestasi**. Laporan masuk dalam status `pending` menunggu verifikasi BK.

### 2.3 Monitoring Siswa Binaan Wali Kelas
1. Buka menu **Poin $\rightarrow$ Monitoring Poin**.
2. Gunakan filter kelas untuk membatasi tampilan pada rombel binaan Anda.
3. Siswa yang masuk ke **Zona Pembinaan 1 (Saldo 1800 - 1899)** atau **Zona Pembinaan 2 (Saldo 1700 - 1799)** wajib diberikan perhatian khusus dan sesi bimbingan bersama guru BK.

---

## 3. Panduan Guru Bimbingan Konseling (BK)

### 3.1 Verifikasi Temuan dan Penanganan Kasus
1. Buka menu **Pelanggaran $\rightarrow$ Kasus Pelanggaran**.
2. Filter tabel dengan status **Temuan (`found`)**.
3. Klik tombol **Detail** (ikon mata) pada kasus yang akan ditangani.
4. Lakukan pemanggilan siswa untuk klarifikasi kejadian.
5. **Langkah Penanganan**:
   - *Jika terbukti melanggar*: Ubah status kasus menjadi **Penanganan (`handling`)**.
     - **PENTING**: Tindakan ini secara otomatis memotong saldo poin kedisiplinan siswa pada buku kas (`point_ledgers`).
     - Terapkan eskalasi poin sesuai tangga toleransi yang tertera pada berkas kasus.
   - *Jika laporan keliru / tidak terbukti*: Klik tombol aksi **Dibuang (`dismissed`)**. Laporan dibatalkan tanpa mengurangi saldo poin siswa.

### 3.2 Program Pemulihan Poin (Restorative Justice)
1. Periksa bagian **Rekomendasi Pemulihan Poin** pada lembar kasus siswa.
2. Berikan penugasan pencapaian prestasi padanan kepada siswa (misal: keaktifan literasi perpustakaan untuk pelanggaran disiplin kelas).
3. Setelah siswa menyelesaikan penugasan, arahkan pencatatan prestasi dengan menautkannya ke nomor kasus yang bersangkutan.
4. Buka menu **Prestasi $\rightarrow$ Rekam Prestasi**, buka detail prestasi siswa, lalu klik **Verifikasi**.
5. Sistem otomatis menambahkan poin reward ke buku kas siswa dan mengembalikan reputasi poinnya.

### 3.3 Penutupan Kasus
1. Setelah seluruh masa bimbingan, sanksi pembinaan, dan komitmen siswa tuntas, buka kembali detail kasus.
2. Ubah status kasus menjadi **Selesai (`closed`)**.
3. Kasus berpindah ke status arsip legalitas formal.

---

## 4. Panduan Tim Kesiswaan

### 4.1 Penanganan Pelanggaran Berat dan Zero Tolerance
1. Kasus dengan bobot **64 poin** (Narkoba, Sajam, Perkelahian, Bullying, dsb.) langsung masuk ke atensi Tim Kesiswaan.
2. Kasus Zero Tolerance **tidak memiliki opsi penebusan prestasi**.
3. Tim Kesiswaan memajukan kasus ke tahap `handling` untuk mencatatkan debit 64 poin secara instan ke sistem.
4. Lakukan pemanggilan orang tua dan buat Berita Acara Pemeriksaan (BAP) resmi.

### 4.2 Tindakan Massal (Bulk Action) Status Kasus
1. Pada tabel indeks kasus pelanggaran, centang kotak seleksi di sebelah kiri nomor kasus yang ingin diproses bersamaan.
2. Pada bilah atas tabel, pilih tahap tujuan pada dropdown **"Majukan ke:"** (misal: *Penanganan* atau *Selesai*).
3. Klik tombol **Majukan**. Sistem memproses seluruh kasus terpilih dalam satu transaksi atomik.

### 4.3 Pemantauan Zona Kritis Sekolah
1. Buka dashboard utama atau menu **Monitoring Poin**.
2. Pantau widget **Distribusi Zona**:
   - Siswa di **Zona Pembinaan 3 (1600 - 1699)**: Koordinasikan surat panggilan orang tua resmi.
   - Siswa di **Zona Pembinaan 4 (1500 - 1599)**: Terbitkan Surat Peringatan (SP) resmi dari Kesiswaan.
   - Siswa di **Zona Intervensi Khusus (< 1500)**: Siapkan berkas rekapitulasi kasus untuk diajukan ke Rapat Dewan Guru dan Kepala Sekolah.

---

## 5. Panduan Kepala Sekolah

### 5.1 Pengawasan Melalui Dasbor Eksekutif
1. Masuk ke halaman **Dashboard**.
2. Amati 4 Kartu Metrik Utama: Total Siswa Aktif, Kasus Aktif Berjalan, Prestasi Terverifikasi, dan Siswa Butuh Pembinaan.
3. Amati grafik **Tren 30 Hari**: Membandingkan angka insiden pelanggaran baru vs kasus yang berhasil diselesaikan.
4. Buka panel **Siswa Poin Terendah** untuk melihat profil siswa yang membutuhkan perhatian struktural segera.

### 5.2 Penerbitan dan Pencetakan Sertifikat Penghargaan
1. Buka menu **Poin $\rightarrow$ Monitoring Poin**.
2. Filter siswa yang berada pada:
   - **Zona Istimewa (> 2500 poin)**: Berhak atas sertifikat *Anugerah Genep Waluya Utama*.
   - **Zona Luar Biasa (2200 - 2499 poin)**: Berhak atas sertifikat *Anugerah Genep Waluya*.
3. Klik tombol **Cetak Sertifikat** pada baris siswa berprestasi.
4. Sistem membuka lembar sertifikat resmi yang telah memuat kop dinas, nomor registrasi, nama siswa, capaian poin, dan tanda tangan digital Kepala Sekolah.
5. Cetak langsung menggunakan fungsi print peramban (`Ctrl + P` / `Cmd + P`).

---

## 6. Panduan Akun Siswa

### 6.1 Pengecekan Saldo Poin Pribadi
1. Masuk menggunakan akun siswa yang telah didaftarkan.
2. Dasbor siswa menampilkan saldo kedisiplinan terkini (dimulai dari 2000 poin pada awal tahun ajaran).
3. Menampilkan status zona pembinaan aktif siswa (contoh: *Zona Normal* atau *Zona Istimewa*).

### 6.2 Riwayat Buku Kas Poin (Buku Pelanggaran Digital)
1. Siswa dapat melihat daftar mutasi poin miliknya secara transparan:
   - Tanggal dan jenis insiden (debit poin).
   - Tanggal dan pencapaian prestasi (kredit poin).
   - Status penanganan kasus yang melibatkan dirinya.
2. Siswa dapat mengetahui rekomendasi prestasi yang harus diraih jika memiliki poin yang terpotong.
