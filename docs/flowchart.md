# Diagram Alir Proses Bisnis (Flowcharts)

Dokumen ini memodelkan diagram alir (*flowchart*) logika bisnis utama pada sistem **Pelanggaran Siswa**.

---

## 1. Flowchart Siklus Penanganan Kasus Pelanggaran

Diagram ini menggambarkan alur kerja sejak temuan pelanggaran dicatat staf di lapangan hingga penuntasan sanksi pembinaan.

```mermaid
flowchart TD
    Start([Mulai: Insiden Pelanggaran Terjadi]) --> InputForm[Staf Buka Form Buat Kasus Baru]
    InputForm --> CheckPrereq{Prasyarat Data Lengkap?<br>Tahun Ajaran, Kelas, Siswa, Kategori}
    
    CheckPrereq -- Tidak --> ModalGuard[Tampilkan Modal Guard Peringatan Data Prasyarat]
    ModalGuard --> RedirectAdd[Staf Lengkapi Data Master Terlebih Dahulu]
    RedirectAdd --> InputForm

    CheckPrereq -- Ya --> SubmitCase[Staf Masukkan Siswa, Kategori, Lokasi & Kronologi]
    SubmitCase --> SaveFound[Sistem Simpan Kasus: Status = 'found'<br>Nomor Kasus: KAS-YYYY-XXXX]
    SaveFound --> NoDebit[Poin Siswa BELUM Dipotong<br>Kasus Masuk Antrean Verifikasi]

    NoDebit --> ReviewCase{Pemeriksaan oleh BK / Kesiswaan:<br>Apakah Temuan Valid?}
    
    ReviewCase -- Tidak Valid / Keliru --> DismissCase[Staf Tekan 'Buang Kasus']
    DismissCase --> SetDismissed[Status Kasus = 'dismissed'<br>Tidak Ada Mutasi Saldo]
    SetDismissed --> EndDismiss([Selesai: Kasus Ditolak])

    ReviewCase -- Valid & Terbukti --> CalcTier[Sistem Cek Riwayat Pelanggaran Serupa:<br>Toleransi Ke-1, Ke-2, atau Ke-3]
    CalcTier --> AdvanceHandling[Staf Tekan 'Majukan ke Penanganan']
    
    AdvanceHandling --> AtomicDebit[Eksekusi Mutasi Atomik DEBIT ke Point Ledger<br>Kunci Baris dengan lockForUpdate]
    AtomicDebit --> SetHandling[Status Kasus = 'handling'<br>Siswa Masuk Masa Pembinaan / Sanksi]
    
    SetHandling --> DoRestorative{Apakah Siswa Memenuhi Sanksi /<br>Menuntaskan Penebusan Prestasi?}
    
    DoRestorative -- Dalam Proses --> MonitorProgress[BK / Kesiswaan Pantau Perkembangan]
    MonitorProgress --> DoRestorative

    DoRestorative -- Selesai --> CloseCase[Staf Tekan 'Selesaikan Kasus']
    CloseCase --> SetClosed[Status Kasus = 'closed'<br>Berkas Diarsipkan Permanen]
    SetClosed --> EndSuccess([Selesai: Pembinaan Tuntas])
```

---

## 2. Flowchart Transaksi Atomik Point Ledger (Append-Only)

Diagram ini mengilustrasikan jaminan integritas buku kas poin dan pencegahan *race-condition* saat pemotongan/penambahan saldo secara bersamaan.

```mermaid
flowchart TD
    StartTx([Pemicu: Kasus ke Handling atau Prestasi Disetujui]) --> BeginDB[Mulai Transaksi DB::transaction]
    BeginDB --> LockRow[SELECT balance_after FROM point_ledgers<br>WHERE student_id = ? AND academic_year_id = ?<br>ORDER BY id DESC LIMIT 1 FOR UPDATE]
    
    LockRow --> CheckExist{Apakah Entri Saldo Sebelumnya Ditemukan?}
    CheckExist -- Ya --> CurrentBal[current_balance = lastEntry.balance_after]
    CheckExist -- Belum Ada --> DefaultBal[current_balance = 2000<br>Nilai Bawaan Saldo Pembuka]

    CurrentBal --> EvalDirection{Jenis Mutasi?}
    DefaultBal --> EvalDirection

    EvalDirection -- DEBIT: Pelanggaran --> CalcDebit[new_balance = current_balance - points]
    EvalDirection -- CREDIT: Prestasi --> CalcCredit[new_balance = current_balance + points]

    CalcDebit --> InsertLedger[INSERT INTO point_ledgers:<br>direction, amount, balance_after, source_type, source_id, verified_by]
    CalcCredit --> InsertLedger

    InsertLedger --> CheckDuplicate{Apakah Sumber Transaksi Unik?<br>UNIQUE source_type, source_id}
    CheckDuplicate -- Duplikat --> RollbackDB[ROLLBACK Transaksi:<br>Cegah Pemotongan Poin Ganda]
    RollbackDB --> ErrorEnd([Gagal: Transaksi Sudah Pernah Diproses])

    CheckDuplicate -- Lolos --> CommitDB[COMMIT Transaksi Basis Data<br>Lepas Kunci Baris]
    CommitDB --> SuccessEnd([Sukses: Saldo Siswa Berhasil Diperbarui])
```

---

## 3. Flowchart Pencatatan & Penebusan Poin Restoratif

Diagram ini memodelkan siklus restoratif di mana pelanggaran tata tertib dipulihkan melalui capaian prestasi (Pedoman Pancawaluya).

```mermaid
flowchart TD
    StartRestorative([Mulai: Siswa Memiliki Kasus Pelanggaran Terbuka]) --> CheckZeroTolerance{Tingkat Pelanggaran Kasus?}
    
    CheckZeroTolerance -- Sangat Berat: Zero Tolerance --> AdminSanction[Sanksi Administratif Langsung dari Kepala Sekolah<br>Tidak Memiliki Jalur Penebusan Prestasi Mandiri]
    AdminSanction --> EndZero([Kasus Diselesaikan Melalui SK Kepala Sekolah])

    CheckZeroTolerance -- Ringan, Sedang, atau Berat --> LookRule[Sistem Ambil Pasangan Penebusan:<br>Tabel redemption_rules]
    LookRule --> ShowRecommendation[Tampilkan Rekomendasi Sub-Prestasi yang Dapat Dikerjakan Siswa]
    
    ShowRecommendation --> StudentAction[Siswa Menyelesaikan Tugas / Lomba / Pembiasaan 7 KAIH]
    StudentAction --> GuruRecord[Guru / Staf Catat Prestasi di Menu Rekam Prestasi]
    GuruRecord --> LinkCase[Staf Menautkan ID Kasus Pelanggaran yang Ditebus]
    
    LinkCase --> SavePending[Simpan Prestasi: Status = 'pending']
    SavePending --> BKReview{Pemeriksaan Berkas Bukti oleh BK / Kesiswaan}
    
    BKReview -- Bukti Ditolak --> RejectRecord[Status Prestasi = 'rejected']
    RejectRecord --> EndReject([Penebusan Gagal])

    BKReview -- Bukti Valid & Disetujui --> ApproveRecord[Status Prestasi = 'verified'<br>Mutasi CREDIT ke Point Ledger]
    ApproveRecord --> CheckCaseBalance{Apakah Poin Prestasi Memadai<br>Untuk Menutup Poin Pelanggaran?}
    
    CheckCaseBalance -- Ya --> AutoClose[Kasus Terkait Ditandai Memenuhi Syarat Selesai]
    CheckCaseBalance -- Sebagian --> PartialRecovery[Poin Pulih Sebagian, Kasus Tetap Berjalan]

    AutoClose --> EndRestorative([Selesai: Karakter Siswa Pulih])
    PartialRecovery --> EndRestorative
```

---

## 4. Flowchart Kenaikan Kelas & Roll-Over Saldo Poin

Diagram ini menggambarkan proses transisi tahun ajaran baru pada kenaikan tingkat rombongan belajar (Kelas X $\rightarrow$ XI $\rightarrow$ XII).

```mermaid
flowchart TD
    StartPromote([Mulai: Akhir Tahun Ajaran]) --> SelectClass[Admin Buka Menu Kelas - Promosi Kenaikan Kelas]
    SelectClass --> PickTarget[Pilih Kelas Asal, Rombel Tujuan, dan Tahun Ajaran Baru]
    
    PickTarget --> ProcessStudents[Iterasi Seluruh Siswa di Kelas Asal]
    
    ProcessStudents --> ArchiveOldEnrollment[Tandai enrollment Lama: status = 'completed'<br>ended_at = now]
    ArchiveOldEnrollment --> CreateNewEnrollment[Buat baris enrollment Baru:<br>student_id, new_class_id, new_academic_year_id, status = 'active']
    
    CreateNewEnrollment --> CheckCarryOver{Kebijakan Saldo Poin Periode Baru}
    
    CheckCarryOver -- Reset Standar Sekolah --> GiveOpening[Terbitkan Mutasi OPENING_BALANCE 2000 Poin Baru]
    CheckCarryOver -- Akumulasi Sisa --> CarryBalance[Bawa Saldo Akhir Sebelumnya Sebagai Saldo Awal]

    GiveOpening --> NextStudent{Masih Ada Siswa Lain?}
    CarryBalance --> NextStudent

    NextStudent -- Ya --> ProcessStudents
    NextStudent -- Selesai --> LockOldYear[Kunci Tahun Ajaran Lama: is_locked = true]
    LockOldYear --> EndPromote([Selesai: Kelas Baru Siap Beroperasi])
```

---

## 5. Flowchart Monitoring Poin & Cetak Sertifikat Kesiswaan

Diagram ini menjelaskan alur seleksi dokumen monitoring kesiswaan berdasarkan acuan resmi *Format Monitoring Program Kesiswaan*.

```mermaid
flowchart TD
    StartMonitor([Mulai: Staf Buka Menu Monitor Poin]) --> FetchRank[Sistem Tampilkan Rekapitulasi Siswa Aktif Terurut Poin]
    FetchRank --> ApplyFilter[Staf Terapkan Filter: Kelas, Tahun Ajaran, atau Zona Tertentu]
    
    ApplyFilter --> SelectRows[Staf Pilih Baris Siswa Mandiri atau Centang 'Pilih Semua']
    SelectRows --> ClickPrint[Staf Tekan 'Cetak Semua' atau Ikon Cetak per Siswa]
    
    ClickPrint --> EvalStudentZone{Evaluasi Saldo & Zona Siswa Terpilih}
    
    EvalStudentZone -- Saldo >= 2500: Zona Istimewa --> FormatIstimewa[Render SERTIFIKAT PENGHARGAAN<br>'Anugerah Genep Waluya Utama']
    EvalStudentZone -- 2200 - 2499: Zona Luar Biasa --> FormatLuarBiasa[Render SERTIFIKAT PENGHARGAAN<br>'Anugerah Genep Waluya']
    EvalStudentZone -- 1900 - 2199: Zona Normal --> FormatNormal[Render SURAT KETERANGAN KEDISIPLINAN<br>'Predikat Disiplin & Perilaku Baik']
    EvalStudentZone -- < 1900: Pembinaan 1-4 & Intervensi --> FormatPembinaan[Render LEMBAR MONITORING & EVALUASI KESISWAAN<br>Rekomendasi Tindak Lanjut dari config/zones.php]

    FormatIstimewa --> EmbedKop[Sematkan Kop Resmi Sekolah, Logo, dan Identitas Murid]
    FormatLuarBiasa --> EmbedKop
    FormatNormal --> EmbedKop
    FormatPembinaan --> EmbedKop

    EmbedKop --> EmbedSign[Sematkan Blok Pengesahan 2 Kolom:<br>Kepala Sekolah dan Wakasek Kesiswaan]
    EmbedSign --> BrowserPrint[Buka Dialog Cetak Browser: A4 Landscape Pas 1 Lembar]
    BrowserPrint --> ExportPDF[Operator Pilih Printer Fisik atau 'Save as PDF']
    ExportPDF --> EndMonitor([Selesai: Dokumen Siap Ditandatangani])
```
