<?php

namespace Database\Seeders;

use App\Models\AchievementCategory;
use App\Models\RedemptionRule;
use App\Models\ViolationCategory;
use Illuminate\Database\Seeder;

/**
 * Kategori Pedoman Pendidikan Karakter Pancawaluya (docs/ROMBAK/pedoman-pancawaluya.pdf).
 *
 * TABEL 1 PENGHARGAAN (R1-R10, 51 sub)  -> achievement_categories.
 *   Kode sub di DB diberi prefix induk ("R2.1") agar unik dan sekaligus
 *   membedakan kategori penghargaan (R) dari kategori pelanggaran (P).
 *   Nama kode dokumen asli tetap tercatat di description.
 *
 * TABEL 2 PELANGGARAN (P1-P5, 53 sub)   -> violation_categories.
 *   Kode A/B/C/D/E skema lama sudah dihentikan — Pedoman Pancawaluya hanya
 *   mengenal P1-P5 dan baris warisan dibersihkan di langkah 0 run().
 *   Tiga kolom poin (ke-I/ke-II/ke-III) = 3 batas toleransi pengulangan,
 *   dipetakan ke mekanisme eskalasi:
 *   points = ke-I, recurring_increment = ke-II - ke-I, max_recurring_points = ke-III.
 *   Contoh P1.1 Terlambat < 10 menit: 5 / 10 / 15 — pelanggaran ke-4 dst.
 *   tetap 15 (batas akhir). Semua baris Tabel 2 eskalasinya +5.
 *
 * Sanksi per sub memakai daftar resmi Bagian 40 (Teguran, Penugasan,
 * Pemanggilan orang tua, Skorsing, Dikeluarkan dari sekolah) yang dipetakan
 * ke tingkat poin: ringan (ke-I <= 5) = Teguran, sedang (6-15) = Teguran dan
 * penugasan, berat (> 15) = Pemanggilan orang tua atau skorsing.
 *
 * Catatan kesadaran dokumen (bukan keputusan seeder):
 * - Tabel 2 tidak punya kolom sanksi per baris — Keterangan hanya SP1/SP2/SP3
 *   dan Force Majeure, jadi penetapan sanksi-tiap-baris di atas adalah turunan
 *   Bagian 40 + tingkat poin, dicatat di description masing-masing baris.
 * - Dokumen TIDAK menyebut penebusan/pemulihan ("penebus" 0 kemunculan),
 *   jadi tidak ada baris yang ditandai zero-tolerance di seeder ini.
 * - Nama kategori R8 hilang di PDF (sel kosong di sumber) — user memilih
 *   "Kegiatan". Baris 9.5 sel POIN-nya kosong — user menetapkan 20 poin.
 *
 * Idempotent: updateOrCreate, aman dijalankan berulang — dipanggil otomatis
 * oleh DatabaseSeeder sebagai satu-satunya sumber kategori pelanggaran &
 * penghargaan.
 */
class PancawaluyaSeeder extends Seeder
{
    /** Kategori induk pelanggaran: kode → [nama, group_type]. */
    private const VIOLATION_PARENTS = [
        'P1' => ['Terlambat', 'terlambat'],
        'P2' => ['Kehadiran', 'kehadiran'],
        'P3' => ['Seragam', 'seragam'],
        'P4' => ['Kerapian dan Penampilan', 'kerapian'],
        'P5' => ['Kedisiplinan', 'kedisiplinan'],
    ];

    /**
     * Sub pelanggaran TABEL 2: [kode dokumen, nama, poin ke-I, ke-II, ke-III].
     * Nama dan poin diambil verbatim dari PDF.
     */
    private const VIOLATIONS = [
        // P1 Terlambat
        ['P1', '1.1', 'Kurang dari 10 menit', 5, 10, 15],
        ['P1', '1.2', 'Lebih dari 10 menit', 10, 15, 20],
        ['P1', '1.3', 'Lebih dari 30 menit', 15, 20, 25],
        // P2 Kehadiran
        ['P2', '2.1', 'Izin tanpa surat keterangan / telepon ke piket sekolah', 5, 10, 15],
        ['P2', '2.2', 'Sakit tanpa surat keterangan dari dokter / orang tua/wali', 5, 10, 15],
        ['P2', '2.3', 'Alpa, tanpa ada keterangan apapun', 5, 10, 15],
        ['P2', '2.4', 'Tidak kembali setelah izin dari sekolah', 10, 15, 20],
        ['P2', '2.5', 'Kembali setelah melebihi waktu yang ditentukan tanpa ada pemberitahuan', 10, 15, 20],
        ['P2', '2.6', 'Keluar lingkungan sekolah pada saat pembelajaran berlangsung tanpa izin dari piket', 10, 15, 20],
        ['P2', '2.7', 'Berada di kantin pada saat pembelajaran sedang berlangsung tanpa izin', 10, 15, 20],
        ['P2', '2.8', 'Tidak masuk selama 2 (dua hari) berturut-turut tanpa ada keterangan', 10, 15, 20],
        ['P2', '2.9', 'Memalsukan surat keterangan dokter atau surat dari orang tua', 10, 15, 20],
        ['P2', '2.10', 'Izin tanpa surat keterangan / telepon ke piket sekolah', 10, 15, 20],
        ['P2', '2.11', 'Sakit tanpa surat keterangan dari dokter / orang tua/wali', 15, 20, 25],
        // P3 Seragam
        ['P3', '3.1', 'Baju tidak dimasukan ke dalam celana atau rok.', 5, 10, 15],
        ['P3', '3.2', 'Menggunakan rok pendek bagi putri.', 5, 10, 15],
        ['P3', '3.3', 'Menggunakan kerudung bagi putri selain berwarna putih polos.', 5, 10, 15],
        ['P3', '3.4', 'Menggunakan atribut tidak lengkap (badge OSIS, badge sekolah, nama peserta didik, dan lokasi sekolah)', 5, 10, 15],
        ['P3', '3.5', 'Nama peserta didik tidak ditempel di dada depan sebelah kanan dengan dasar putih, tulisan hitam, dan bentuk persegi panjang.', 5, 10, 15],
        ['P3', '3.6', 'Lokasi sekolah tidak ditempel di bagian tangan sebelah kanan dengan dasar putih, tulisan hitam, dan bentuk persegi panjang', 5, 10, 15],
        ['P3', '3.7', 'Badge OSIS tidak ditempel di dada depan sebelah kiri.', 5, 10, 15],
        ['P3', '3.8', 'Badge sekolah tidak ditempel di bagian tangan sebelah kiri.', 5, 10, 15],
        ['P3', '3.9', 'Topi sekolah tidak sesuai dengan ketentuan.', 5, 10, 15],
        ['P3', '3.10', 'Tidak menggunakan sabuk berwarna hitam.', 5, 10, 15],
        ['P3', '3.11', 'Tidak menggunakan sepatu berwarna hitam dan tidak menggunakan kaos kaki.', 5, 10, 15],
        ['P3', '3.12', 'Menggunakan perhiasan atau aksesoris yang berlebihan.', 5, 10, 15],
        ['P3', '3.13', 'Menggunakan rompi, jaket, sweater selama di sekolah.', 5, 10, 15],
        ['P3', '3.14', 'Mencoret, menggambar atau mewarnai seragam di luar ketentuan.', 10, 15, 20],
        // P4 Kerapian dan Penampilan
        ['P4', '4.1', 'Berkuku panjang dan dicat atau kutek.', 5, 10, 15],
        ['P4', '4.2', 'Bertato/menggambari kulit tubuh dengan apapun.', 5, 10, 15],
        ['P4', '4.3', 'Bercukur gundul atau plontos.', 5, 10, 15],
        ['P4', '4.4', 'Mewarnai rambut.', 5, 10, 15],
        ['P4', '4.5', 'Berambut panjang dan berkuncir bagi peserta didik laki-laki.', 5, 10, 15],
        ['P4', '4.6', 'Menata rambut dengan model yang tidak sesuai sebagai pelajar.', 5, 10, 15],
        ['P4', '4.7', 'Menggunakan perhiasan dan aksesoris', 5, 10, 15],
        ['P4', '4.8', 'Merias wajah secara berlebihan.', 5, 10, 15],
        ['P4', '4.9', 'Memakai softlens kecuali softlens kesehatan.', 5, 10, 15],
        // P5 Kedisiplinan
        ['P5', '5.1', 'Menggunakan gawai tanpa sepengetahuan dan arahan dan bimbingan dari guru.', 10, 15, 20],
        ['P5', '5.2', 'Makan dan minum ketika pembelajaran sedang berlangsung tanpa izin dari guru yang bersangkutan', 5, 10, 15],
        ['P5', '5.3', 'Meminjam barang, nomor PIN / pasword orang lain tanpa izin pemilik', 10, 15, 20],
        ['P5', '5.4', 'Terlambat pengumpulan tugas', 5, 10, 15],
        ['P5', '5.5', 'Menyontek/melakukan kecurangan saat ujian', 20, 25, 30],
        ['P5', '5.6', 'Menyebarkan rumor atau informasi bohong (hoaks).', 5, 10, 15],
        ['P5', '5.7', 'Merokok, mengonsumsi minuman beralkohol, memakai dan mengedarkan narkotika, psikotropika, dan zat adiktif lainnya.', 10, 15, 20],
        ['P5', '5.8', 'Pergaulan bebas dan berperilaku menyimpang.', 20, 25, 30],
        ['P5', '5.9', 'Berkelahi atau melakukan tawuran.', 20, 25, 30],
        ['P5', '5.10', 'Membuang sampah sembarangan dan merusak lingkungan.', 20, 25, 30],
        ['P5', '5.11', 'Melakukan perbuatan vandalisme.', 20, 25, 30],
        ['P5', '5.12', 'Melakukan perundungan baik fisik, verbal, dan digital.', 20, 25, 30],
        ['P5', '5.13', 'Membawa barang-barang terlarang, membahayakan, dan mengancam keselamatan diri dan orang lain.', 20, 25, 30],
        ['P5', '5.14', 'Membawa alat-alat yang dipergunakan untuk bermain judi.', 20, 25, 30],
        ['P5', '5.15', 'Mengambil dan menyimpan barang milik orang lain tanpa izin dan tanpa hak.', 20, 25, 30],
        ['P5', '5.16', 'Melanggar norma dan hukum yang berlaku di sekolah, masyarakat, dan negara', 20, 25, 30],
    ];

    /** Kategori induk penghargaan: kode → [nama, group_type]. */
    private const ACHIEVEMENT_PARENTS = [
        'R1' => ['Pengembangan Keagamaan', 'keagamaan'],
        'R2' => ['Kejujuran', 'karakter'],
        'R3' => ['Prestasi Akademis', 'akademik'],
        'R4' => ['Kedisiplinan', 'karakter'],
        'R5' => ['Pengembangan Sosial', 'sosial'],
        'R6' => ['Kepemimpinan', 'kepemimpinan'],
        'R7' => ['Kebangsaan', 'nonakademik'],
        'R8' => ['Kegiatan', 'nonakademik'],
        'R9' => ['Peduli Lingkungan', 'kebersihan'],
        'R10' => ['Kewirausahaan', 'nonakademik'],
    ];

    /**
     * Sub penghargaan TABEL 1: [kode dokumen, nama, poin].
     * Kode DB = "R" + kode dokumen ("2.1" → "R2.1").
     */
    private const ACHIEVEMENTS = [
        // R1 Pengembangan Keagamaan
        ['1.1', 'Melaksanakan praktik-praktik keagamaan sesuai agama dan kepercayaanya masing-masing.', 20],
        // R2 Kejujuran
        ['2.1', 'Menyampaikan / melaporkan barang temuan', 20],
        ['2.2', 'Berkata jujur dalam kesaksian', 20],
        ['2.3', 'Melaporkan tindakan pelanggaran / negatif yang dilakukan orang lain kepada pihak sekolah / berwajib', 20],
        ['2.4', 'Jujur dalam menyelesaikan ujian', 10],
        // R3 Prestasi Akademis
        ['3.1', 'Berhasil menjadi peringkat pertama di kelas setiap semester', 20],
        ['3.2', 'Berhasil menjadi peringkat 5 besar di kelas setiap semester', 15],
        ['3.3', 'Berhasil menjadi peringkat 10 besar di kelas setiap semester', 10],
        ['3.4', 'Aktif dalam kegiatan belajar', 10],
        ['3.5', 'Menghasilkan karya inovatif yang menunjang proses pembelajaran', 20],
        ['3.6', 'Menjadi peserta didik berprestasi di tingkat sekolah', 10],
        ['3.7', 'Menjadi peserta didik berprestasi di tingkat kota', 20],
        ['3.8', 'Menjadi peserta didik berprestasi di tingkat provinsi', 30],
        ['3.9', 'Menjadi peserta didik berprestasi di tingkat nasional', 40],
        ['3.10', 'Memperoleh beasiswa prestasi dari instansi / lembaga / yayasan', 20],
        // R4 Kedisiplinan
        ['4.1', 'Menyimpan alat-alat pembelajaran di tempatnya', 10],
        ['4.2', 'Tidak pernah melanggar tata tertib minimal 3 bulan berturut-turut', 20],
        ['4.3', 'Tidak pernah melanggar tata tertib minimal 6 bulan berturut-turut', 30],
        ['4.4', 'Tidak pernah melanggar tata tertib minimal 9 bulan berturut-turut', 40],
        ['4.5', 'Tidak pernah melanggar tata tertib minimal 12 bulan berturut-turut', 50],
        // R5 Pengembangan Sosial
        ['5.1', 'Membantu / menolong orang yang kena musibah', 10],
        ['5.2', 'Terlibat dalam aksi sosial, seperti bakti sosial ke rumah yatim atau masyarakat, donor darah dan kegiatan sosial lainnya', 15],
        // R6 Kepemimpinan
        ['6.1', 'Mengikuti kegiatan LDKS (Latihan Dasar Kepemimpinan Siswa)', 10],
        ['6.2', 'Menjadi ketua OSIS / MPK selama satu periode', 20],
        ['6.3', 'Menjadi pengurus OSIS / MPK selama satu periode', 10],
        ['6.4', 'Menjadi ketua kegiatan ekstrakurikuler', 15],
        ['6.5', 'Menjadi ketua kelompok belajar', 10],
        // R7 Kebangsaan
        ['7.1', 'Mengikuti kegiatan Pendidikan Kesadaran Bela Negara', 10],
        ['7.2', 'Melaksanakan nilai-nilai Pancasila dan UUD 1945 dalam keseharian', 10],
        ['7.3', 'Menjadi petugas upacara di sekolah', 10],
        ['7.4', 'Menjadi petugas upacara di tingkat kota', 20],
        ['7.5', 'Menjadi petugas upacara di tingkat provinsi', 30],
        ['7.6', 'Menjadi petugas upacara di tingkat nasional', 40],
        ['7.7', 'Menjadi duta budaya / seni / pertukaran pelajar', 30],
        // R8 Kegiatan (nama induk hilang di PDF — pilihan user)
        ['8.1', 'Aktif dalam kegiatan ekstrakurikuler wajib', 10],
        ['8.2', 'Aktif dalam kegiatan ekstrakurikuler lainnya', 10],
        ['8.3', 'Menjadi peserta perlombaan / kegiatan yang mewakili sekolah', 5],
        ['8.4', 'Menjadi juara di tingkat sekolah', 5],
        ['8.5', 'Menjadi juara di tingkat kota / kabupaten', 20],
        ['8.6', 'Menjadi juara di tingkat provinsi', 30],
        ['8.7', 'Menjadi juara di tingkat nasional', 40],
        ['8.8', 'Menjadi juara di tingkat internasional', 50],
        // R9 Peduli Lingkungan
        ['9.1', 'Membuang dan memilah sampah pada tempatnya / sesuai dengan jenis sampah', 10],
        ['9.2', 'Menghasilkan / menemukan karya inovatif yang bermanfaat untuk pelestarian lingkungan dan ramah lingkungan', 20],
        ['9.3', 'Memberikan ide / gagasan yang dapat mengatasi masalah lingkungan', 20],
        ['9.4', 'Menjadi motivator dan inovator dalam memelihara dan mengembangkan potensi lokal (seni dan budaya)', 20],
        ['9.5', 'Mengikuti kegiatan Reboisasi / menanam pohon', 20], // sel POIN kosong di PDF — 20 dari keputusan user
        // R10 Kewirausahaan (10.3 memang tidak ada di dokumen)
        ['10.1', 'Memberi ide / gagasan yang dapat menambah nilai ekonomis', 10],
        ['10.2', 'Aktif mengembangkan jiwa kewirausahaan di sekolah', 10],
        ['10.4', 'Membantu meringankan beban orang tua / membiayai sendiri dengan usaha mandiri', 10],
        ['10.5', 'Memiliki buku tabungan dengan saldo secara berkala bertambah', 10],
    ];

    /**
     * Kode pelanggaran warisan skema lama (A/B/C/D/E) yang dihentikan.
     * Pedoman Pancawaluya hanya mengenal P1-P5, jadi baris ini dibersihkan
     * agar form kasus tidak lagi menawarkan kode a,b,c,d,e.
     */
    private const LEGACY_VIOLATION_CODES = [
        'A', 'A.08', 'B', 'B.15', 'B.16', 'B.17',
        'C', 'C.01', 'D', 'D.02', 'E.05',
        'TEST_1500', 'TEST_500',
    ];

    /**
     * Sub-prestasi warisan (kode numerik dokumen lama) yang digantikan R1-R10.
     * Tidak dihapus sembarangan: rekam prestasi lama boleh tetap menunjuk baris
     * ini, jadi statusnya diturunkan ke draft — bukan di-delete.
     */
    private const LEGACY_ACHIEVEMENT_CODES = [
        '1.3', '2.1', '2.2', '2.3', '2.4', '3.4', '3.5', '4.3', '4.4',
        '5.2', '5.3', '7.1', '7.7', '8.1', '9.1', '9.2',
    ];

    /**
     * Redemption rules kanonik: [kode pelanggaran, kode prestasi].
     * Hanya memakai kode P (pelanggaran) dan R (penghargaan) Pedoman
     * Pancawaluya. Sub P dipetakan hanya bila padanannya jelas dari substansi
     * pelanggaran — sisanya memakai fallback tingkat severity.
     */
    private const RULES = [
        // P1 Terlambat — disiplin kehadiran -> R4.2 (3 bln taat)
        ['P1.1', 'R4.2'], // <10 menit -> R4.2
        ['P1.2', 'R4.2'], // >10 menit -> R4.2
        ['P1.3', 'R4.2'], // >30 menit -> R4.2
        // P2 Kehadiran
        ['P2.1', 'R4.2'], // Izin tanpa surat -> R4.2 komitmen hadir
        ['P2.2', 'R4.2'], // Sakit tanpa surat -> R4.2
        ['P2.3', 'R4.2'], // Alpa -> R4.2
        ['P2.4', 'R4.2'], // Tidak kembali setelah izin -> R4.2
        ['P2.5', 'R4.2'], // Kembali lewat waktu tanpa kabar -> R4.2
        ['P2.6', 'R4.2'], // Keluar saat belajar tanpa izin -> R4.2
        ['P2.7', 'R3.4'], // Di kantin saat belajar tanpa izin -> R3.4 aktif belajar (fokus balik)
        ['P2.8', 'R4.2'], // Tidak masuk 2 hari tanpa ket -> R4.2
        ['P2.9', 'R2.2'], // Palsu surat dokter/ortu -> R2.2 jujur kesaksian (inti=ketidakjujuran)
        ['P2.10', 'R4.2'], // Izin tanpa surat (duplikat P2.1, poin 10) -> R4.2
        ['P2.11', 'R4.2'], // Sakit tanpa surat (duplikat P2.2, poin 15) -> R4.2
        // P3 Seragam
        ['P3.1', 'R4.2'], // Baju tidak dimasukkan -> R4.2
        ['P3.2', 'R4.2'], // Rok pendek -> R4.2
        ['P3.3', 'R1.1'], // Kerudung bukan putih polos -> R1.1 praktik keagamaan (cermin A.3)
        ['P3.4', 'R4.2'], // Atribut tidak lengkap -> R4.2
        ['P3.5', 'R4.2'], // Nama tidak ditempel -> R4.2
        ['P3.6', 'R4.2'], // Lokasi tidak ditempel -> R4.2
        ['P3.7', 'R4.2'], // Badge OSIS tidak ditempel -> R4.2
        ['P3.8', 'R4.2'], // Badge sekolah tidak ditempel -> R4.2
        ['P3.9', 'R4.2'], // Topi tidak sesuai -> R4.2
        ['P3.10', 'R4.2'], // Sabuk tidak hitam -> R4.2
        ['P3.11', 'R4.2'], // Sepatu/kaos kaki -> R4.2
        ['P3.12', 'R4.2'], // Perhiasan berlebihan -> R4.2 (cermin A.4)
        ['P3.13', 'R4.2'], // Rompi/jaket/sweater -> R4.2 (cermin A.18)
        ['P3.14', 'R9.1'], // Coret seragam -> R9.1 buang/pilah sampah (cermin A.17 merawat fasilitas)
        // P4 Kerapian
        ['P4.1', 'R9.1'], // Kuku panjang/dicat -> R9.1 kebersihan (cermin A.8)
        ['P4.2', 'R5.2'], // Tato -> R5.2 aksi sosial (padanan terdekat A.6->5.3 etika, R5.3 tidak ada)
        ['P4.3', 'R4.2'], // Gundul/plontos -> R4.2 (cermin A.2)
        ['P4.4', 'R4.2'], // Warnai rambut -> R4.2
        ['P4.5', 'R4.2'], // Rambut panjang berkuncir -> R4.2
        ['P4.6', 'R4.2'], // Model rambut tidak sesuai -> R4.2
        ['P4.7', 'R4.2'], // Perhiasan/aksesoris -> R4.2
        ['P4.8', 'R4.2'], // Rias berlebihan -> R4.2 (cermin A.7)
        ['P4.9', 'R4.2'], // Softlens non-kesehatan -> R4.2 (cermin A.5)
        // P5 Kedisiplinan
        ['P5.1', 'R3.4'], // Gawai tanpa izin -> R3.4 aktif belajar
        ['P5.2', 'R3.4'], // Makan/minum saat belajar tanpa izin -> R3.4
        ['P5.3', 'R2.1'], // Pinjam barang/PIN tanpa izin -> R2.1 lapor temuan (integritas milik orang)
        ['P5.4', 'R3.4'], // Terlambat tugas -> R3.4
        ['P5.5', 'R2.4'], // Nyontek -> R2.4 jujur ujian (cermin B.34, exact)
        ['P5.6', 'R2.2'], // Hoaks -> R2.2 jujur kesaksian (inti=ketidakjujuran)
        ['P5.7', 'R4.3'], // Rokok/miras/narkoba -> R4.3 6 bln taat (komitmen jangka panjang)
        ['P5.8', 'R5.2'], // Pergaulan bebas -> R5.2 aksi sosial
        ['P5.9', 'R7.1'], // Tawuran -> R7.1 Bela Negara (anti-kekerasan)
        ['P5.10', 'R9.1'], // Buang sampah -> R9.1 (cermin B.7/B.4)
        ['P5.11', 'R9.1'], // Vandalisme -> R9.1 merawat sarana (fix: B.7->9.1, sebelumnya R4.2)
        ['P5.12', 'R5.2'], // Bullying -> R5.2 aksi sosial (cermin B.25/Fani)
        ['P5.13', 'R4.3'], // Barang terlarang -> R4.3 komitmen jangka panjang
        ['P5.14', 'R10.5'], // Alat judi -> R10.5 tabungan (kelola keuangan benar vs judi)
        ['P5.15', 'R2.1'], // Ambil barang tanpa izin -> R2.1 lapor temuan
        ['P5.16', 'R7.2'], // Langgar norma/hukum -> R7.2 Pancasila/UUD (taat norma)
    ];

    public function run(): void
    {
        // 0. Bersihkan pelanggaran warisan skema lama (A/B/C/D/E + kode TEST_*).
        //    redemption_rules ikut terhapus lewat cascadeOnDelete, jadi tidak ada
        //    padanan yatim yang menunjuk kategori yang sudah tidak ada.
        ViolationCategory::whereIn('code', self::LEGACY_VIOLATION_CODES)->delete();

        // 1. Kategori induk pelanggaran (TABEL 2).
        $vParents = [];
        foreach (self::VIOLATION_PARENTS as $code => [$name, $groupType]) {
            $vParents[$code] = ViolationCategory::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'group_type' => $groupType,
                    'severity' => 'ringan',
                    'points' => 0,
                    'description' => "Kategori induk {$name} (Pedoman Pancawaluya — TABEL 2). Sub-kategori di bawahnya yang dipilih saat membuat kasus.",
                    'status' => 'active',
                ]
            );
        }

        // 2. Sub pelanggaran — eskalasi poin dan sanksi diturunkan dari Tabel 2.
        foreach (self::VIOLATIONS as [$parentCode, $docCode, $name, $p1, $p2, $p3]) {
            // docCode sudah mengandung nomor induk ("1.1" di bawah P1) —
            // kode DB = prefix induk saja: 1.1 -> P1.1, 5.16 -> P5.16.
            ViolationCategory::updateOrCreate(
                ['code' => "P{$docCode}"],
                [
                    'name' => $name,
                    'parent_id' => $vParents[$parentCode]->id,
                    'severity' => $this->severityOf($p1),
                    'points' => $p1,
                    'is_recurring' => $p2 > $p1,
                    'recurring_increment' => $p2 - $p1,
                    'max_recurring_points' => $p3,
                    'group_type' => self::VIOLATION_PARENTS[$parentCode][1],
                    'sanksi' => $this->sanksiOf($p1),
                    'description' => $this->violationDescription($docCode, $p1, $p2, $p3),
                    'status' => 'active',
                ]
            );
        }

        // 3. Kategori induk penghargaan (TABEL 1) — poin induk 0, sub yang berbobot.
        $aParents = [];
        foreach (self::ACHIEVEMENT_PARENTS as $code => [$name, $groupType]) {
            $aParents[$code] = AchievementCategory::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'points' => 0,
                    'group_type' => $groupType,
                    'parent_id' => null,
                    'description' => "Kategori penghargaan induk (Pedoman Pancawaluya — TABEL 1). Kumulasi poin sub di bawahnya.",
                    'status' => 'active',
                ]
            );
        }

        // 4. Sub penghargaan.
        foreach (self::ACHIEVEMENTS as [$docCode, $name, $points]) {
            $parentCode = 'R' . explode('.', $docCode)[0];
            AchievementCategory::updateOrCreate(
                ['code' => "R{$docCode}"],
                [
                    'name' => $name,
                    'points' => $points,
                    'group_type' => self::ACHIEVEMENT_PARENTS[$parentCode][1],
                    'parent_id' => $aParents[$parentCode]->id,
                    'description' => "Pedoman Pancawaluya TABEL 1 — kode dokumen: {$docCode}.",
                    'status' => 'active',
                ]
            );
        }

        // 5. Sub-prestasi warisan (kode numerik dokumen lama) → draft.
        //    Tidak dihapus: rekam prestasi lama boleh tetap menunjuk baris ini.
        foreach (self::LEGACY_ACHIEVEMENT_CODES as $legacyCode) {
            $parentCode = 'R' . explode('.', $legacyCode)[0];
            $legacy = AchievementCategory::where('code', $legacyCode)->first();
            if ($legacy && isset($aParents[$parentCode])) {
                $legacy->update([
                    'status' => 'draft',
                    'parent_id' => $aParents[$parentCode]->id,
                ]);
            }
        }

        // 6. Hapus redemption lawas yang menunjuk sub tersingkir —
        //    baris draft tidak boleh muncul di rekomendasi pemulihan.
        $supersededIds = AchievementCategory::whereIn('code', self::LEGACY_ACHIEVEMENT_CODES)->pluck('id');
        RedemptionRule::whereIn('achievement_category_id', $supersededIds)->delete();

        // 7. Pasangkan redemption kanonik (idempotent, poin = poin sub tujuan).
        //    Replace semantics: bila tujuan berubah (mis. P5.11 R4.2 → R9.1),
        //    rule lama ke tujuan tidak tercantum ikut dihapus — updateOrCreate
        //    saja tidak membersihkannya (kuncinya pasangan id).
        $wanted = [];
        foreach (self::RULES as [$vCode, $aCode]) {
            $violation = ViolationCategory::where('code', $vCode)->first();
            $achievement = AchievementCategory::where('code', $aCode)->first();
            if (! $violation || ! $achievement) {
                continue;
            }
            $wanted[$violation->id][] = $achievement->id;
            RedemptionRule::updateOrCreate(
                [
                    'violation_category_id' => $violation->id,
                    'achievement_category_id' => $achievement->id,
                ],
                ['poin_penebus' => $achievement->points]
            );
        }
        foreach ($wanted as $violationId => $achievementIds) {
            RedemptionRule::where('violation_category_id', $violationId)
                ->whereNotIn('achievement_category_id', $achievementIds)
                ->delete();
        }
    }

    /** Tingkat dari poin ke-I (turunan Tabel 2, bukan label dokumen). */
    private function severityOf(int $pointsFirst): string
    {
        return match (true) {
            $pointsFirst <= 5 => 'ringan',
            $pointsFirst <= 15 => 'sedang',
            default => 'berat',
        };
    }

    /** Sanksi dari daftar Bagian 40 dipetakan ke tingkat poin. */
    private function sanksiOf(int $pointsFirst): string
    {
        return match (true) {
            $pointsFirst <= 5 => 'Teguran',
            $pointsFirst <= 15 => 'Teguran dan penugasan',
            default => 'Pemanggilan orang tua atau skorsing',
        };
    }

    private function violationDescription(string $docCode, int $p1, int $p2, int $p3): string
    {
        $desc = "Pedoman Pancawaluya TABEL 2 — kode dokumen: {$docCode}. "
            . "Poin pelanggaran ke-I/II/III: {$p1}/{$p2}/{$p3}. "
            . "Sanksi ditetapkan dari daftar Bagian 40 sesuai tingkat poin.";

        // Keterangan 4 Tabel 2 — Force Majeure, dicatat sebagai catatan kasus,
        // bukan sanksi kategori (berlaku bila terbukti, bukan otomatis).
        if (in_array($docCode, ['5.8', '5.16'], true)) {
            $desc .= ' Force Majeure (Keterangan 4): bila terbukti bersifat hukum/asusila, '
                . 'peserta didik dikembalikan ke orang tua tanpa didahului Surat Peringatan.';
        }

        return $desc;
    }
}
