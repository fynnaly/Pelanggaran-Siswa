# Task 1 & 5 — Promote Confirmation Fix + Dashboard Lengkap

> **Branch:** `feat/crud-optimization` | **Date:** 2026-09-19
> **Depends on:** `2026-09-18_143000-ledger-balance-source-bulk-promotion.md` (ledger fix, bulk promote, source label — DONE)

---

## Task 1 — Fix Promote: Tambah Konfirmasi Sebelum Submit (P0 BUG)

### Goal
Bulk promote tanpa popup/confirmation = user bisa pindahkan siswa tanpa sadar. Tambah `confirm()` sebelum submit.

### Root Cause
`resources/views/students/index.blade.php` — form `bulkPromoteForm` langsung submit via POST tanpa `onsubmit="return confirm(...)"`. Backend OK, bug murni frontend.

### Steps

**Step 1.1** — Tambah `onsubmit` di form tag
```php
// resources/views/students/index.blade.php
// Cari: <form id="bulkPromoteForm" action="{{ route('classes.promote') }}" method="POST">
// Ganti:
<form id="bulkPromoteForm" action="{{ route('classes.promote') }}" method="POST"
      onsubmit="return confirm('Anda yakin ingin memindahkan siswa terpilih ke kelas target? Tindakan ini tidak dapat dibatalkan.')">
```

**Step 1.2** — Verify
```sh
grep -n "confirm\|bulkPromoteForm" resources/views/students/index.blade.php
# Expected: <form ... onsubmit="return confirm(...)">
php -l resources/views/students/index.blade.php
# Expected: No syntax errors
```

**Done.** Single-line fix, no risk.

---

## Task 5 — Dashboard Lengkap (P2 POLISH)

### Goal
Dashboard sudah kirim data (totalCasesDismissed, totalCasesDone, lowPointStudents, activeAcademicYear) tapi view tidak render semua. Tambah section breakdown status + kartu tahun ajaran aktif yang informatif.

### Current State (Verified)
- `DashboardController@index` — kirim 10 variabel: `totalStudentsActive`, `totalCases`, `totalCategoriesActive`, `totalCategories`, `activeAcademicYear`, `totalCasesFound`, `totalCasesValidated`, `totalCasesDone`, `totalCasesDismissed`, `recentCases`, `lowPointStudents`
- `dashboard.blade.php` — render 4 kartu utama + 4 breakdown + recentCases + lowPointStudents. **Sudah lengkap.**
- Satu fix sudah dilakukan: `latest_balance` (N+1 fix) di line 94.

### Analysis
Dashboard **sudah lengkap** dari sisi data & render. Yang belum ada:
1. ~~Section breakdown status~~ — Sudah ada (Ditemukan/Divalidasi/Selesai/Dibuang)
2. ~~LowPointStudents~~ — Sudah ada
3. ~~RecentCases~~ — Sudah ada

Yang **bisa ditambah** (optional polish, bukan bug):
- Progress bar visual untuk breakdown status
- "Kasus menunggu validasi" highlight (totalCasesFound - totalCasesValidated)
- Tahun ajaran aktif info lebih detail

### Steps

**Step 5.1** — Tambah progress bar di breakdown status kasus
```php
// resources/views/dashboard.blade.php
// Setelah </div> di line 54 (breakdown 4 kartu), tambah:
@if($totalCases > 0)
<div class="mt-4 bg-white shadow-sm sm:rounded-lg p-4">
    <h3 class="text-sm font-semibold text-gray-700 mb-3">Progres Penanganan Kasus</h3>
    <div class="w-full h-4 bg-gray-200 rounded-full overflow-hidden flex">
        @php
            $foundPct = $totalCases ? ($totalCasesFound / $totalCases) * 100 : 0;
            $validatedPct = $totalCases ? ($totalCasesValidated / $totalCases) * 100 : 0;
            $donePct = $totalCases ? ($totalCasesDone / $totalCases) * 100 : 0;
        @endphp
        @if($foundPct > 0)
            <div class="bg-blue-500 h-full" style="width: {{ $foundPct }}%" title="Ditemukan: {{ $totalCasesFound }}"></div>
        @endif
        @if($validatedPct > 0)
            <div class="bg-emerald-500 h-full" style="width: {{ $validatedPct }}%" title="Divalidasi: {{ $totalCasesValidated }}"></div>
        @endif
        @if($donePct > 0)
            <div class="bg-gray-500 h-full" style="width: {{ $donePct }}%" title="Selesai: {{ $totalCasesDone }}"></div>
        @endif
    </div>
    <div class="flex justify-between mt-2 text-xs text-gray-500">
        <span>Ditemukan {{ $totalCasesFound }}</span>
        <span>Divalidasi {{ $totalCasesValidated }}</span>
        <span>Selesai {{ $totalCasesDone }}</span>
        <span>Dibuang {{ $totalCasesDismissed }}</span>
    </div>
</div>
@endif
```

**Step 5.2** — Tambah "Menunggu Validasi" highlight card
```php
// Di grid kartu utama (line 11-34), tambah kartu ke-5 (grid-cols-2 md:grid-cols-5):
<div class="glass r-lg p-4">
    <p class="text-xs text-gray-500 uppercase tracking-wide">Menunggu Validasi</p>
    <p class="mt-1 text-2xl font-semibold text-amber-600">{{ $totalCasesFound - $totalCasesValidated }}</p>
</div>
```

**Step 5.3** — Verify
```sh
php -l resources/views/dashboard.blade.php
# Expected: No syntax errors
npm run build
# Expected: ✓ built
```

---

## Risks & Tradeoffs

| Item | Risk | Mitigation |
|------|------|------------|
| Task 1 (confirm dialog) | `confirm()` native = UX buruk di mobile | Acceptable MVP. Upgrade ke Blade modal di future. |
| Task 5 (progress bar) | Division by zero jika 0 kasus | Guard `@if($totalCases > 0)` |
| Task 5 (menunggu validasi) | Negatif jika data corrupt | Gunakan `\max(0, $totalCasesFound - $totalCasesValidated)` |

## Open Questions

1. **User preference:** Progress bar style — apakah `rounded-full` (pill) atau `rounded` (flat)? Pilih pill (modern).
2. **Task 5 status:** Dashboard sudah render semua data. Task 5 di plan asli bilang "tambah section" — apakah progress bar + highlight card cukup, atau butuh chart?

---

## Execution Order

```
Task 1 (1 menit) → npm run build → Task 5 (5 menit) → npm run build → Done
```
