# Latar Belakang, Identifikasi Masalah, dan Tujuan Sistem

## 1. Latar Belakang (For What)
Pengelolaan kedisiplinan dan tata tertib siswa di institusi pendidikan kejuruan (SMK) merupakan fondasi pembentukan karakter kerja (soft skills) dan kesiapan industri (work-readiness). Pada sistem konvensional, pencatatan pelanggaran sering kali bersifat punitif (hanya menghukum), dicatat secara manual di buku fisik guru Bimbingan Konseling (BK), dan tidak memiliki mekanisme pemulihan perilaku yang terukur.

Aplikasi Pelanggaran Siswa dirancang untuk mengubah paradigma disiplin dari sekadar hukuman menjadi sistem pembinaan karakter berbasis penghargaan dan pemulihan (restorative discipline). Sistem ini mengintegrasikan pencatatan pelanggaran bertingkat dengan pencatatan prestasi siswa sebagai sarana penebusan poin, didukung buku kas audit (point ledger) yang akuntabel.

---

## 2. Masalah yang Dihadapi (Problem Statement)

### 2.1 Fragmentasi Data dan Hilangnya Riwayat Siswa
- **Masalah**: Pencatatan pelanggaran siswa dicatat pada lembar kertas atau spreadsheet terpisah oleh wali kelas, guru piket, dan guru BK.
- **Dampak**: Terjadi perbedaan data antar-staf, data hilang saat pergantian tahun ajaran, dan riwayat perilaku siswa tidak terdokumentasi secara konsisten saat siswa naik kelas.

### 2.2 Sanksi yang Tidak Terstandarisasi dan Subjektif
- **Masalah**: Penentuan tingkat pelanggaran dan bobot sanksi sangat bergantung pada persepsi masing-masing guru yang menangani.
- **Dampak**: Timbul rasa ketidakadilan di kalangan siswa dan orang tua. Tidak ada rujukan formal tingkat eskalasi jika pelanggaran dilakukan berulang kali.

### 2.3 Ketiadaan Jalur Pemulihan (Restorative Justice)
- **Masalah**: Siswa yang melanggar hanya menerima sanksi atau surat panggilan tanpa memiliki kesempatan untuk memperbaiki reputasi poin kedisiplinannya melalui aksi positif.
- **Dampak**: Siswa merasa dicap buruk (labeling), mengalami demotivasi, dan tidak terdorong untuk berprestasi atau memperbaiki sikap.

### 2.4 Keterlambatan Penanganan Siswa di Zona Kritis
- **Masalah**: Pihak sekolah (Kepala Sekolah dan Koordinator Kesiswaan) baru mengetahui adanya masalah ketika siswa sudah berada pada pelanggaran sangat berat atau akumulasi poin sudah melewati batas toleransi.
- **Dampak**: Tidak ada intervensi dini dari wali kelas atau guru BK untuk mencegah pelanggaran berulang.

### 2.5 Integritas Data dan Manipulasi Nilai Poin
- **Masalah**: Pada sistem spreadsheet atau database biasa yang mengizinkan update/delete langsung pada saldo siswa, nilai poin rentan dimanipulasi tanpa jejak audit.
- **Dampak**: Hilangnya transparansi dan kredibilitas data saat pelaporan ke orang tua atau dinas pendidikan.

---

## 3. Mengapa Solusi Ini Dipilih (Why)

### 3.1 Model Saldo Poin Positif Awal dan Buku Kas (Point Ledger)
Sistem menetapkan saldo awal positif (2000 poin) pada setiap siswa di awal tahun ajaran. 
- Pelanggaran mendebit (mengurangi) poin.
- Prestasi mengkredit (menambah atau memulihkan) poin.
- Seluruh mutasi dicatat secara append-only (hanya tambah baris, tanpa edit atau hapus) pada tabel mutasi ledger, menjamin kepatuhan audit forensik internal sekolah.

### 3.2 Delapan Zona Skor Pembinaan
Untuk menghindari generalisasi, saldo poin dikelompokkan ke dalam 8 zona pembinaan yang menentukan pihak penanggung jawab secara presisi:
1. **Zona Istimewa (>2500)**: Berhak mendapatkan penghargaan Sertifikat Anugerah Utama.
2. **Zona Luar Biasa (2200 - 2499)**: Berhak mendapatkan Sertifikat Anugerah Genep Waluya.
3. **Zona Normal (1900 - 2199)**: Kondisi standar siswa tertib.
4. **Zona Pembinaan 1 (1800 - 1899)**: Pendampingan intensif oleh Wali Kelas.
5. **Zona Pembinaan 2 (1700 - 1799)**: Kolaborasi pendampingan Wali Kelas dan Guru BK.
6. **Zona Pembinaan 3 (1600 - 1699)**: Pemanggilan resmi orang tua/wali siswa oleh pihak sekolah.
7. **Zona Pembinaan 4 (1500 - 1599)**: Penanganan langsung oleh Tim Kesiswaan.
8. **Zona Intervensi Khusus (<1500)**: Penanganan tingkat pimpinan melalui Rapat Dewan Guru hingga Kepala Sekolah.

### 3.3 Penyelarasan Hierarki Poin Skala 8/16/32/64 dan Dokumen Pancawaluya
Aturan pelanggaran dan prestasi diselaraskan langsung dengan Pedoman Pancawaluya:
- Kategori Ringan (8 poin), Sedang (16 poin), Berat (32 poin), dan Sangat Berat / Zero Tolerance (64 poin).
- Eskalasi otomatis hingga 3 tingkat toleransi bagi pelanggaran berulang (base -> base*2 -> base*4 dengan plafon 32 poin).
- Kategori Zero Tolerance (narkoba, senjata tajam, bullying, pelecehan) dipatok flat pada 64 poin tanpa eskalasi dan tidak dapat ditebus dengan poin prestasi biasa (wajib pembinaan intensif).

### 3.4 Penebusan Terstruktur (Redemption Rules)
Setiap pelanggaran yang dapat ditebus dipasangkan dengan rekomendasi sub-prestasi terkait (misal: pelanggaran terlambat ditebus dengan keaktifan belajar/ibadah, vandalisme ditebus dengan program kebersihan sarana). Poin pemulihan hanya cair setelah prestasi diverifikasi oleh staf berwenang.

---

## 4. Tujuan dan Sasaran Sistem

1. **Digitalisasi Sentral**: Menyediakan basis data tunggal catatan perilaku, pelanggaran, dan prestasi siswa yang dapat diakses oleh seluruh staf berwenang secara real-time.
2. **Standardisasi Prosedur**: Menegakkan alur kerja resmi: temuan (found) -> penanganan dan pemotongan poin (handling) -> tuntas (closed).
3. **Mendorong Prestasi**: Memberi insentif bagi siswa untuk memperbaiki diri melalui pencapaian akademik, non-akademik, sosial, dan keagamaan.
4. **Transparansi & Akuntabilitas**: Menghasilkan dokumen legalitas formal (lembar penanganan kasus, kartu riwayat siswa, dan sertifikat penghargaan) yang dilengkapi kop resmi dan tanda tangan digital.
