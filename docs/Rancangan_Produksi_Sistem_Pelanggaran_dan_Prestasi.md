# Rancangan Produksi Sistem Pelanggaran dan Prestasi Siswa

**Status:** Blueprint implementasi produksi dan prototipe interaktif.  
**Arsitektur target:** Laravel MVC, Supabase PostgreSQL, Tailwind CSS, Chart.js, Laravel Sanctum, Supabase Storage, dan Android Studio (Kotlin + Material You 3).  
**Sumber SOP utama:** Standar Operasional Prosedur Penanganan Siswa Bermasalah yang diberikan oleh pengguna.

> Sistem ini dirancang khusus untuk staf sekolah yang mendapat otorisasi. **Siswa tidak memiliki akun, peran, menu, ataupun endpoint akses** pada aplikasi.

## 1. Ringkasan Solusi

Sistem ini merupakan *case management system* untuk mendokumentasikan pelanggaran dan prestasi siswa secara objektif, bertahap, serta dapat diaudit. Setiap kasus bergerak melalui alur SOP dari temuan sampai evaluasi akhir. Poin baru menjadi rekap resmi setelah klasifikasi dan validasi sesuai tingkat kewenangan. Penanganan tidak diposisikan semata-mata sebagai hukuman, tetapi sebagai proses pembinaan yang memuat klarifikasi, konseling, komunikasi wali kelas dan orang tua, tindak lanjut, serta evaluasi perubahan perilaku.

| Area | Hasil rancangan |
|---|---|
| Operasional | Pencatatan temuan, klarifikasi, klasifikasi, validasi, penanganan, monitoring, dan penutupan kasus |
| Data | Data master siswa/rombel, aturan, tingkat pelanggaran, bobot poin, panduan, sanksi, prestasi, dan dokumen |
| Pengendalian | RBAC, *scope* rombel/penugasan, jejak audit append-only, status transisi terkendali, dan tenggat tindak lanjut |
| Analitik | Dashboard kasus aktif, keterlambatan tindak lanjut, akumulasi poin, distribusi, tren, perbandingan kelas/kategori, dan kasus berulang |
| Integrasi | Import/export Excel, laporan PDF/XLSX, object storage, notifikasi, REST API untuk Android |

## 2. Arsitektur Produksi yang Direkomendasikan

Implementasi produksi menggunakan Laravel sebagai pusat aturan bisnis. Browser dan Android tidak boleh menulis langsung ke tabel kasus melalui kunci yang memiliki hak istimewa. Laravel memverifikasi identitas, menerapkan Policy per sumber daya, menulis audit event, lalu berkomunikasi dengan PostgreSQL dan object storage melalui kredensial sisi server. Laravel menyediakan *Gates* untuk kemampuan lintas sumber daya dan *Policies* untuk aturan yang melekat pada model, yang sesuai dengan pola dokumentasi resminya.[1]

```text
Web staf (Blade/Inertia + Tailwind + Chart.js)     Android (Kotlin + Compose Material 3)
                    │                                          │
                    └──────────── Laravel API / MVC ───────────┘
                                      │
          Laravel Sanctum · Policies · Form Requests · Queue · Audit Listener
                         │                         │
         Supabase PostgreSQL + RLS          Supabase Storage / S3
                         │                         │
            tabel relasional, view aman      bukti, surat, ekspor, metadata
```

| Lapisan | Teknologi | Rancangan profesional |
|---|---|---|
| Web aplikasi | Laravel 12, Blade atau Inertia, Tailwind CSS, Chart.js | Antarmuka staf yang responsif; Chart.js dipilih karena menyediakan tipe grafik umum serta opsi konfigurasi yang dapat disesuaikan.[3] |
| API | Laravel Sanctum, Laravel API Resources, API versioning `/api/v1` | Token aplikasi Android dengan *ability*; respons konsisten, paginasi cursor, dan *rate limiting* |
| Domain | Service class, Action class, Event/Listener, Laravel Policies | Proses kasus tidak berada di controller; controller hanya menerima request dan menyerahkan aturan pada layanan domain |
| Database | Supabase PostgreSQL | PostgreSQL sebagai sumber kebenaran relasional dan RLS sebagai pertahanan berlapis untuk skema yang terpapar |
| Berkas | Supabase Storage atau S3 kompatibel | Metadata ada pada PostgreSQL, berkas biner hanya pada object storage, akses melalui URL bertanda tangan berumur pendek |
| Proses asinkron | Laravel Queue + Redis **(tambahan)** | Pembuatan PDF/XLSX, pengiriman notifikasi, kompresi berkas, dan pengingat tenggat tidak membebani request pengguna |
| Android | Kotlin, Jetpack Compose, Material You 3, Retrofit/OkHttp, Room terenkripsi **(tambahan)** | Konsumsi API staf dengan cache terkendali dan sinkronisasi saat koneksi tersedia |

## 3. Peran dan Matriks Otorisasi

Hak akses harus mengikuti prinsip *least privilege*. Ketika seorang staf terautentikasi, peran aktifnya dan cakupan tugasnya menentukan data yang dapat dilihat maupun diubah. Tidak cukup hanya menyembunyikan menu pada frontend; Laravel Policy, query scope, dan RLS harus menolak operasi yang tidak diizinkan.

| Peran | Membaca | Membuat / mengubah | Validasi / keputusan | Batas wajib |
|---|---|---|---|---|
| Admin | Seluruh data sesuai kebijakan sekolah | Akun staf, data master, konfigurasi, koreksi administratif berjejak | Konfigurasi dan audit | Tidak menghapus fakta kasus; gunakan arsip dengan alasan |
| Wakil Kepala Sekolah / Kesiswaan | Kasus lintas rombel, laporan agregat | Penugasan, tindak lanjut, pemanggilan orang tua | Validasi sedang/berat, penutupan yang membutuhkan keputusan | Tidak mengelola token atau kredensial sistem |
| Guru BK | Kasus yang ditugaskan, siswa dalam cakupan layanan | Klarifikasi, konseling, rencana tindak lanjut, monitoring | Usulan klasifikasi; penutupan sesuai batas kebijakan | Tidak mengubah bobot poin atau aturan master |
| Wali Kelas | Siswa dan kasus di rombel binaan | Konfirmasi, catatan monitoring, komunikasi wali | Tidak memvalidasi pelanggaran berat | Tidak melihat kasus rombel lain kecuali ditugaskan |
| Guru / Pelapor | Temuan yang dibuatnya dan status ringkas tindak lanjut | Temuan awal dan lampiran awal | Tidak dapat mengesahkan atau menutup kasus sendiri | Tidak melihat bukti terbatas milik kasus lain |
| Pimpinan | Dashboard dan laporan agregat | Tidak ada mutasi operasional | Tidak ada | Hanya-baca; akses bukti sensitif harus dikecualikan secara eksplisit |
| Siswa | Tidak ada | Tidak ada | Tidak ada | Tidak dibuatkan peran, akun, atau endpoint |

### Kebijakan Laravel yang perlu dibuat

| Policy | Kemampuan | Contoh aturan |
|---|---|---|
| `DisciplineCasePolicy` | `viewAny`, `view`, `create`, `startClarification`, `classify`, `validate`, `assign`, `close`, `archive` | Wali kelas hanya dapat melihat siswa di rombelnya; pelapor hanya dapat menulis temuan dan tidak dapat memvalidasi sendiri |
| `StudentPolicy` | `view`, `viewLedger`, `viewAchievements`, `import` | Menolak akses jika siswa berada di luar *scope* rombel/penugasan |
| `CaseDocumentPolicy` | `view`, `upload`, `download` | Bukti dengan `restricted` tidak dapat diunduh oleh Pimpinan atau Pelapor kecuali ditugaskan |
| `ReportPolicy` | `exportPoints`, `exportCase`, `viewAggregate` | Pimpinan hanya memperoleh agregat; nama siswa pada laporan lintas sekolah bisa dipseudonimkan **(saran)** |
| `MasterDataPolicy` | `manageRules`, `manageWeights`, `manageAcademicYears` | Hanya Admin; Kesiswaan dapat diberi hak usul tetapi tidak terbit otomatis |

## 4. Alur SOP Digital dan Status Kasus

Status hanya boleh bergeser maju melalui transisi yang ditentukan. Kembali ke tahap sebelumnya dilakukan sebagai transisi resmi dengan alasan, bukan dengan mengubah data lama secara diam-diam. Perubahan status menulis record pada `case_status_events` dan `audit_logs`.

| Urutan | Status sistem | Data minimum | Otoritas utama | Keluaran |
|---:|---|---|---|---|
| 1 | `found` | Nomor kasus, siswa, pelapor, waktu, tempat, kronologi, aturan awal | Guru/Pelapor, BK, Kesiswaan | Temuan dicatat |
| 2 | `clarifying` | Pihak terkait, waktu konfirmasi, ringkasan pernyataan, bukti | BK, Wali Kelas, Kesiswaan | Klarifikasi terdokumentasi |
| 3 | `classifying` | Kategori, tingkat, bobot, poin usulan, dasar aturan | BK/Kesiswaan | Klasifikasi siap diperiksa |
| 4 | `awaiting_validation` | Validator, alasan, pemeriksaan kelengkapan | Kesiswaan/Admin | Keputusan validasi |
| 5 | `handling` | Konseling/sanksi, petugas, tenggat, surat/pernyataan/panggilan orang tua | BK, Kesiswaan, Wali Kelas | Rencana tindak lanjut berjalan |
| 6 | `monitoring` | Indikator perubahan, catatan periodik, rekomendasi | BK, Wali Kelas | Perkembangan tercatat |
| 7 | `closed` | Hasil, catatan akhir, tanggal penutupan, penutup berwenang | Kesiswaan/BK sesuai kebijakan | Kasus selesai |
| 8 | `archived` | Alasan pengarsipan dan pelaku | Admin/Kesiswaan | Rekam historis dipertahankan |

### Aturan poin

Poin disimpan sebagai *ledger* append-only, bukan angka yang dapat diedit bebas pada profil siswa. Saat validasi disahkan, layanan `PointLedgerService` membuat satu transaksi `student_point_ledgers` dengan `case_id` unik. Jika keputusan dibatalkan secara legal, sistem membuat entri penyesuaian terpisah dengan referensi audit, bukan menghapus entri lama.

| Kondisi | Perilaku poin |
|---|---|
| Kasus temuan/klarifikasi belum valid | Tidak masuk rekap resmi |
| Klasifikasi valid | Menambah `point_delta` sesuai bobot efektif pada tanggal kejadian |
| Koreksi keputusan | Menulis transaksi pembalik + alasan + otorisasi |
| Kasus diarsipkan sebelum validasi | Tidak membentuk ledger |
| Pemindahan tahun ajaran | Ledger tidak dihapus; rekap difilter menurut tahun ajaran |

## 5. Model Data PostgreSQL

Tabel dibagi menjadi domain master, operasional kasus, dokumentasi, pemantauan, dan tata kelola. Gunakan UUID atau ULID sebagai identitas publik untuk endpoint API, sementara *surrogate key* internal dapat tetap menggunakan bigint/identity.

| Domain | Tabel | Kolom penting |
|---|---|---|
| Identitas staf | `users`, `staff_profiles`, `user_role_assignments` **(tambahan)** | `auth_user_id`, `role`, `employee_number`, `is_active` |
| Akademik | `academic_years`, `school_classes`, `students` | tahun aktif, rombel, wali kelas, NISN, status siswa |
| Aturan | `rule_references`, `severity_levels`, `violation_categories`, `point_weights`, `guidance_types`, `sanction_types` | kode aturan, masa berlaku, tingkat, bobot poin, tipe tindakan |
| Kasus | `discipline_cases`, `case_clarifications`, `case_assignments`, `case_actions`, `case_status_events` | nomor kasus, kronologi, status, officer, deadline, catatan hasil |
| Dokumen | `stored_files`, `case_documents`, `parent_summonses` | object key, MIME, ukuran, checksum, jenis dokumen, level akses |
| Poin dan prestasi | `student_point_ledgers`, `student_achievements`, `evaluation_snapshots` **(tambahan)** | delta poin, saldo, verifikasi prestasi, ringkasan evaluasi |
| Tata kelola | `audit_logs`, `notifications`, `report_exports` **(tambahan)** | aktor, perubahan sebelum/sesudah, penerima, masa berlaku ekspor |

### Indeks dan integritas yang wajib

| Objek | Integritas / indeks |
|---|---|
| `discipline_cases` | `UNIQUE(case_number)`, indeks `(student_id, status)`, `(follow_up_due_at, status)`, `(reporter_id)` |
| `student_point_ledgers` | `UNIQUE(case_id)` untuk mencegah poin valid tercatat dua kali; indeks `(student_id, created_at)` |
| `case_assignments` | indeks `(case_id, officer_id)` dan cek bahwa officer merupakan staf aktif |
| `point_weights` | indeks `(violation_category_id, severity_level_id, is_active)` serta validasi rentang masa berlaku tidak tumpang tindih **(tambahan)** |
| `audit_logs` | indeks `(entity_type, entity_id, created_at)`; tidak diberikan endpoint update/delete |
| `stored_files` | `UNIQUE(object_key)` dan `checksum` untuk deteksi unggahan duplikat **(tambahan)** |

## 6. Supabase PostgreSQL dan RLS

Pada Supabase, RLS harus dinyalakan untuk setiap tabel pada skema yang terpapar. Dokumentasi Supabase menjelaskan bahwa policy menjadi aturan per-baris, sementara *grant* tetap menentukan operasi dasar yang diizinkan; karena itu keduanya harus disetel bersamaan.[2] Untuk sistem ini, pendekatan paling aman adalah menjadikan Laravel sebagai satu-satunya pihak yang menjalankan mutasi operasional dengan kredensial sisi server, lalu mencabut semua *grant* `anon` dan `authenticated` dari tabel kasus yang sensitif.

```sql
-- Contoh minimum untuk tabel sensitif. Jalankan melalui migration Supabase.
alter table public.discipline_cases enable row level security;
revoke all on table public.discipline_cases from anon, authenticated;

-- Jika aplikasi Laravel adalah satu-satunya akses data, jangan berikan grant browser.
-- service_role tetap disimpan HANYA di environment Laravel dan tidak pernah dikirim ke browser/mobile.

-- Jika suatu saat Android perlu akses baca langsung, buat policy eksplisit dan terbatas:
grant select on public.student_summary to authenticated;
alter table public.student_summary enable row level security;
create policy "staff can read scoped student summaries"
on public.student_summary for select to authenticated
using (
  (select auth.uid()) is not null
  and exists (
    select 1 from public.staff_profiles sp
    where sp.auth_user_id = (select auth.uid()) and sp.is_active = true
  )
);
```

Policy harus diuji untuk kasus `anon`, staf terotorisasi, staf di luar rombel, pelapor pemilik kasus, dan staff dengan peran lebih tinggi. Supabase juga merekomendasikan pengujian operasi `select`, `insert`, `update`, dan `delete` secara terpisah pada RLS.[2]

## 7. Kontrak API REST untuk Android dan Web

Semua endpoint berada di bawah `/api/v1`, memakai JSON, dan membutuhkan token Sanctum kecuali endpoint masuk/refresh. Tanggal disimpan UTC dengan ISO-8601 dan dikonversi di klien menjadi zona waktu sekolah. Respons list memakai cursor pagination untuk skalabilitas.

| Metode | Endpoint | Izin utama | Tujuan |
|---|---|---|---|
| `GET` | `/me` | Semua staf aktif | Profil, role, permission, dan *scope* aktif |
| `GET` | `/dashboard` | Semua staf aktif | Ringkasan yang sudah dibatasi role/scope |
| `GET` | `/students?class_id=&q=` | Sesuai `StudentPolicy` | Pencarian siswa tanpa mengekspos siswa di luar scope |
| `GET` | `/students/{publicId}` | Sesuai `StudentPolicy` | Profil, saldo poin, pelanggaran, prestasi, dan rekomendasi |
| `GET` | `/cases` | Sesuai `DisciplineCasePolicy` | Filter periode, siswa, kelas, kategori, status, officer |
| `POST` | `/cases` | Guru/Pelapor, BK, Kesiswaan | Pencatatan temuan awal |
| `GET` | `/cases/{caseNumber}` | Sesuai `DisciplineCasePolicy` | Detail fakta, timeline SOP, tindakan, dan dokumen yang diizinkan |
| `POST` | `/cases/{id}/clarifications` | BK/Wali/Kesiswaan | Catatan klarifikasi terstruktur |
| `POST` | `/cases/{id}/classification` | BK/Kesiswaan | Usulan kategori, tingkat, dan poin |
| `POST` | `/cases/{id}/validation` | Kesiswaan/Admin | Validasi atau pengembalian klasifikasi |
| `POST` | `/cases/{id}/actions` | BK/Kesiswaan | Konseling, sanksi, surat, panggilan orang tua, follow-up |
| `POST` | `/cases/{id}/transitions` | Berdasarkan stage | Mengubah status dengan alasan wajib |
| `POST` | `/cases/{id}/attachments/presign` | Sesuai `CaseDocumentPolicy` | Menghasilkan URL unggah terbatas dan metadata draft |
| `GET` | `/reports/point-recap` | BK/Kesiswaan/Pimpinan | Rekap agregat/terbatas sesuai role |
| `POST` | `/imports/students` | Admin | Memulai import XLSX dengan hasil validasi per baris |

### Bentuk respons standar

```json
{
  "data": { "id": "01J...", "status": "handling" },
  "meta": { "request_id": "req_..." },
  "errors": []
}
```

Untuk kegagalan otorisasi, API mengembalikan `403`. Untuk sumber daya yang perlu disembunyikan dari pengguna di luar scope, gunakan respons `404` sesuai pertimbangan keamanan Policy.[1]

## 8. Dokumentasi, Lampiran, dan Laporan

Lampiran dapat berupa bukti awal, formulir laporan, berita acara, surat pernyataan, surat panggilan orang tua, surat peringatan, dan arsip kesiswaan. Database hanya menyimpan metadata; objek berkas berada di bucket privat. Akses unduhan harus dihasilkan untuk satu berkas, satu pengguna, dan waktu singkat, lalu dicatat sebagai audit event.

| Kontrol | Ketentuan |
|---|---|
| Ukuran/MIME | Izinkan hanya tipe yang ditentukan, misalnya PDF/JPG/PNG/DOCX/XLSX; batas ukuran diputuskan sekolah, misalnya 10 MB per berkas **(saran)** |
| Nama objek | Gunakan key acak: `cases/{case_ulid}/{file_ulid}`; jangan memakai NISN atau nama siswa pada key |
| Antivirus | Pindai unggahan sebelum dokumen berstatus tersedia **(tambahan)** |
| Akses | URL bertanda tangan maksimal 5–15 menit **(saran)**; tidak ada bucket publik |
| Penghapusan | Soft-delete metadata dan retensi sesuai kebijakan sekolah; jangan hapus rekam kasus tanpa otorisasi |
| Ekspor | Pembuatan PDF/XLSX melalui queue; berkas hasil masuk bucket privat dengan masa berlaku unduhan |

Untuk Excel, sediakan template terpisah untuk siswa baru, perpindahan kelas, siswa keluar, master kategori, dan bobot poin. Proses import menjalankan validasi NISN/NIS, duplikasi, format tanggal, kelas aktif, dan ringkasan error per baris sebelum pengguna mengonfirmasi commit.

## 9. Dashboard dan Laporan

Dashboard dirancang sebagai *command center* institusional: navy menandai otoritas, teal menandai data/aksi tervalidasi, rose menandai risiko atau tenggat lewat, dan amber menandai capaian/perhatian. Tampilan tidak menggunakan ornamen yang tidak memiliki makna status.

| Widget | Rumus / sumber | Aksi pengguna |
|---|---|---|
| Total kasus | `count(discipline_cases)` dengan filter periode/scope | Buka register terfilter |
| Kasus aktif | Status selain `closed` dan `archived` | Lihat antrean penanganan |
| Tindak lanjut lewat | `follow_up_due_at < now()` dan status aktif | Prioritaskan officer yang bertugas |
| Poin tertinggi | `sum(student_point_ledgers.point_delta)` per siswa | Buka profil dan rekomendasi pembinaan |
| Distribusi kategori | `count` per kategori | Analisis pola tata tertib |
| Distribusi tingkat | `count` per tingkat terverifikasi | Kaji proporsi risiko |
| Tren bulanan | `count` per bulan kejadian | Bandingkan tren periode |
| Kasus berulang | Siswa dengan kategori sama ≥ n kali dalam periode | Buat rekomendasi konseling/rapat kasus |

Laporan perlu mendukung filter periode, tahun ajaran, siswa, rombel, kategori, tingkat, status, petugas, dan aturan. Laporan Pimpinan secara default sebaiknya agregat. Laporan bernama siswa harus memerlukan alasan bisnis dan izin eksplisit **(saran)**.

## 10. Notifikasi dan Tindak Lanjut

Notifikasi bersifat *event-driven*, bukan hanya pengingat umum. Ketika temuan dibuat, sistem memberi tahu BK/kesiswaan sesuai kategori atau rombel. Ketika status masuk `awaiting_validation`, validator menerima notifikasi. Ketika tenggat mendekat atau lewat, officer serta atasan penanggung jawab menerima pengingat. Catatan notifikasi disimpan di tabel `notifications` dengan `read_at`, sedangkan pengiriman kanal eksternal (email/push/WhatsApp) harus dapat diaktifkan terpisah sesuai izin sekolah **(tambahan)**.

| Peristiwa | Penerima | Kanal awal yang disarankan |
|---|---|---|
| Temuan baru | BK, Kesiswaan, Wali Kelas terkait | In-app + email institusi **(saran)** |
| Validasi dibutuhkan | Kesiswaan/Admin | In-app + email |
| Panggilan orang tua terjadwal | Petugas kasus dan Wali Kelas | In-app |
| Tenggat H-1 / lewat | Officer, BK/Kesiswaan | In-app + email |
| Kasus ditutup | Pelapor dan tim kasus | In-app |

## 11. Aplikasi Android Studio

Aplikasi Android hanya untuk staf. Halaman utama menampilkan dashboard sesuai peran, daftar kasus yang tersaring, detail siswa, profil poin, pelanggaran, prestasi, serta tugas tindak lanjut yang dibebankan kepada pengguna.

| Komponen | Rancangan |
|---|---|
| UI | Kotlin, Jetpack Compose, Material You 3, tema warna mengikuti identitas institusional web |
| Network | Retrofit + OkHttp + interceptor token + *request id* |
| Auth | Laravel Sanctum token; simpan token melalui Jetpack Security / EncryptedSharedPreferences |
| Cache | Room dengan data minimal dan TTL; jangan simpan file bukti secara permanen di perangkat **(saran)** |
| Offline | Hanya draft temuan terenkripsi **(tambahan)**; mutasi final tetap dikonfirmasi server saat sinkronisasi |
| Keamanan | Screenshot prevention pada halaman bukti sensitif **(tambahan)**, logout menghapus token/cache, deteksi root bila kebijakan sekolah mengharuskan **(tambahan)** |

## 12. Tahap Implementasi yang Disarankan

| Tahap | Cakupan | Kriteria selesai |
|---:|---|---|
| 1 | Laravel skeleton, Supabase project, autentikasi staf, role/Policy, data tahun ajaran/rombel/siswa | Siswa tidak dapat login; semua endpoint mutasi ditolak tanpa role staf |
| 2 | Aturan, kategori, bobot, temuan, klarifikasi, klasifikasi, audit | Kasus dapat direkam dan transisi SOP tercatat |
| 3 | Validasi, ledger poin, tindakan BK, panggilan orang tua, dokumen privat | Poin tidak dapat diduplikasi; berkas tidak publik |
| 4 | Dashboard, laporan, Excel, PDF, notifikasi | Filter/scope benar dan eksport masuk antrean |
| 5 | Android, pengujian keamanan, UAT SOP, pelatihan staf | UAT ditandatangani Kesiswaan/BK dan matriks akses tervalidasi |

## 13. Checklist Keamanan sebelum Produksi

| Kontrol | Verifikasi |
|---|---|
| Otorisasi | Unit/integration test untuk seluruh kombinasi peran dan `DisciplineCasePolicy` |
| Database | RLS aktif pada tabel yang diekspos, grant `anon` dicabut, dan pengujian allow/deny tersedia |
| API | Form Request, rate limiting, pagination, UUID publik, error tanpa data sensitif |
| Dokumen | Bucket privat, URL singkat, pemeriksaan MIME/ukuran/checksum, scan berkas **(tambahan)** |
| Audit | Semua create/update/transisi/unduh/ekspor mencatat aktor, waktu, dan target |
| Poin | Ledger append-only, transaksi atomik, nomor kasus unik, kontrol idempotensi |
| Privasi | Retensi data, pembatasan laporan bernama siswa, SOP akses bukti, dan persetujuan kebijakan sekolah |
| Kualitas | PHPUnit/Pest, Laravel feature test, test RLS, test Android, dan UAT berbasis SOP |

## References

[1]: https://laravel.com/docs/13.x/authorization "Laravel Authorization Documentation"
[2]: https://supabase.com/docs/guides/database/postgres/row-level-security "Supabase Row Level Security Documentation"
[3]: https://www.chartjs.org/docs/ "Chart.js Documentation"
