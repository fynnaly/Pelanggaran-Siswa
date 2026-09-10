# Alur Sistem Pelanggaran & Prestasi Siswa

Alur ini menggambarkan bagaimana data pelanggaran dan prestigeir kelola dari awal hingga selesai, melalui tangan staf sekolah. Setiap tahap memiliki aturan ketat agar tidak ada "potongan poin" yang tidak jelas.

---

## Alur Utama: Found → Validated → Done (Kasus Pelanggaran)

**Tahap 1: Found (Guru melapor)**
- Guru melihat/adanya pelanggaran
- Klik "Lapor" di aplikasi → sistem bikin entri `DisciplineCase` baru
- Status awal: `found`
- **Aturan:** Tidak ada debit/poin dikurang sebelum BK/validasi

**Tahap 2: Validated (BK/Admin verifikasi)**
- BK/Wali/Admin membaca kasus, mungkin memanggil siswa/saksi
- Jika cukup bukti → ubah status `validated`
- Sistem otomatis **mengurangi poin** dari saldo siswa (ledger debit)
- Jika bukti tidak cukup → ubah status `dismissed` (poin **tidak** dikurang)

**Tahap 3: Done (Penindak lanjut)**
- Siswa sudah dikenai sanksi, kasus tertutup
- Status diubah menjadi `done`
- Riwayat tetap disimpan untuk laporan tahunan

```
┌─────────────────────────────────────────────────────┐
│                     GURU                          │
│ 1. Melihat/adanya pelanggaran                    │
│    ↓                                               │
│ 2. Klik "Lapor" → DisciplineCase status: found   │
│    ↓ (BK verifikasi)                              │
│ 3. Status → validated → poin dikurang (ledger)    │
│    ↓                                               │
│ 4. Status → done → kasus tertutup                │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│                     STAF (BK/Admin)              │
│ Validasi cukup bukti?                              │
│   ┌─────────────────┐ YA                       ┌─────────────────┐
│   │ Status → validated │                   │ Status → dismissed │
│   │ Poin dikurang      │                   │ Poin tetap        │
│   └─────────────────┘                   └─────────────────┘
│                                        NO
│                               ↓
│                      Kasus dibuang, catatan tetap
└─────────────────────────────────────────────────────┘
```

---

## Alur Prestasi: Pending → Verified → Closed (Kasus Prestasi)

Sistem mirip tapi lawan arah: **menambah** poin ke saldo siswa.

```
┌─────────────────────────────────────────────────────┐
│                     GURU/STAF                      │
│ 1. Mengajukan prestasi (prestasi olahraga, akademik)│
│    ↓                                               │
│ 2. Status: pending → BK/Admin verifikasi           │
│    ↓                                               │
│ 3. Cukup bukti?                                  │
│   ┌────────────────────────────┐                  │
│   │ Status → verified          │                  │ Status → rejected │
│   │ Poin ditambah (ledger)     │                  │ Poin tetap        │
│   └────────────────────────────┘                  └────────────────────────────┘
│                                        NO
│                               ↓
│                      Pengajuan dibuang, catatan tetap
└─────────────────────────────────────────────────────┘
```

---

## Mengapa 4 Model Ini Adalah "Inti"?

**1. Student (Subjek/Siswa)**
- Wajib ada. Semua kasus (pelanggaran/prestasi) mengacu ke siswa.
- Tanpa Student, kasus itu "drift" — tidak ada subjeknya.
- FK ke User (login), kelas, NISN/NIS — identitas lengkap.

**2. ViolationCategory (Kategori Pelanggaran)**
- Menentukan **seberapa besar poin** yang dikurang.
- Enum `severity`: ringan → sedang → berat = 10 → 30 → 80 poin.
- Tanpa kategori, guru nggak tau berapa poin yang harus dibuang.

**3. DisciplineCase (Kasus Pelanggaran itu sendiri)**
- **HANYA** entitas di sini yang mengontrol alur `found → validated → done`.
- Setiap kasus punya: nomor kasus, siswa, kategori pelanggaran, lokasi, deskripsi, status, validated_by, validated_at.
- Ini adalah "laporan insiden" — tanpa ini, nggak ada catatan resmi.

**4. PointLedger (Append-only Audit Trail Poin)**
- **Hanya menambah/mengurangi.** Tidak pernah update atau hapus data lama.
- Setiap perubahan saldo: direction (credit/debit), amount, balance_after, reason, transaction_type, verified_by, verified_at.
- **Ini adalah "kasir poin"** — saldo awal 2000, tiap kali ada kasus diurangi, tiap kali ada prestasi ditambah.
- Tanpa ini, saldo siswa hanyalah angka di kepala — tidak ada bukti, tidak ada rekam jejak.

---

## Alur Sederhana (1 Halaman)

```
Siswa awal: 2000 poin

1. Guru lapor pelanggaran ringan → DisciplineCase found → -10 poin → saldo 1990
2. BK validasi cukup → DisciplineCase validated → -20 poin → saldo 1970 (disini boleh tambahan)
3. Guru ajukan prestasi → AchievementRecord pending → +50 poin → saldo 2020
4. Admin verifikasi → AchievementRecord verified → +50 poin → saldo 2070 (final)
5. Kasus selesai → DisciplineCase done → nggak ngurangi lagi, hanya catatan

Total poin: 2070 / 2000 awal + total debit - total credit
```

---

## Kontrol & Keamanan

| Aturan | Penjelasan |
|---|---|
| `found` → `validated` baru debit | Tidak boleh potong poin sebelum validasi BK |
| `validated` → `dismissed` | Poin **tidak** dikembalikan (kasus sudah dibuang) |
| `found` → `done` | Bisa lewat `validated` atau langsung, tapi debit sudah pernah |
| `verified_by` + `verified_at` | Wajib ada di setiap ledger entry (who + when) |
| `student_id + academic_year_id + transaction_type` | Unique — tidak bisa buka saldo dua kali di tahun yang sama |

---

## Referensi Lain (Support)

| Model | Fungsi | Kapan Aktif |
|---|---|---|
| AcademicYear | Tahun pelajaran (2023/2024, 2024/2025) | Selalu, FK ke kelas & ledger |
| SchoolClass | Kelas (XII-PPLG-1, XI-AKT-1) | Input awal via seeder |
| AchievementCategory | Kategori prestasi (prestasi, kontes, ospek) | Pelaporan prestige |
| AchievementRecord | Riwayat usaha siswa | Prestasi, bisa rejected |

---

**Catatan:** Alur ini **hard rule**. Ga boleh lewat atau "kasih jeda". Setiapalihannya harus melalui kode (controller + policy), bukan manual SQL. Jika perlu menambah kategori baru → seeder + migrate, ga boleh langsung ubah di DB.