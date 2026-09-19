# Ledger Balance Fix, Sumber Professional, dan Naik Kelas Massal — Pelanggaran Siswa

> **Status:** Code & view sudah diterapkan & diverifikasi. Plan ini hanya menyimpan ringkasan + design.md untuk website sekolahan.

---

## Current Context / Assumptions (Code Verified)

All three fixes from the earlier plan have been implemented and verified via `php artisan tinker`:

### 1. Bug saldo (P0) — `app/Http/Controllers/DisciplineCaseController.php`
- **Dilakukan:** `DB::transaction` + `lockForUpdate()` di method `validate()`, pisah variabel `$lastBalance` dan `$balanceAfter = $lastBalance - $points`. 
- **Verifikasi:** Siswa dengan opening 2000 + validasi 1500 poin → `balance_after` = 500 (bukan 2000). `php artisan tinker` berhasil menampilkan `balance_after=500`.

### 2. Sumber profesional di ledger (P2)
- **Dilakukan:** Tambah method `sourceLabel()` di `app/Models/PointLedger.php` + ganti tampilan `show.blade.php` pakai `$pointLedger->sourceLabel()`.
- **Verifikasi:** `PointLedger::latest()->first()->sourceLabel()` mengembalikan `KAS-2026-0001` (bukan `App\Models\DisciplineCase #1`).

### 3. Naik kelas massal + drilldown (P1 FEATURE)
- **Dilakukan:** 
  - Route `POST classes/promote` sebelum `Route::resource('classes', ...)` di `routes/web.php`
  - Method `SchoolClassController@promote()` dengan validasi `student_ids[]` + `class_id`
  - `SchoolClassController@index` pakai `withCount('students')` → `$class->students_count`
  - `classes/index.blade.php`: `students_count` + `<a href="students.index?class_id=...">Lihat Siswa</a>` (text-only)
  - `StudentController@index` filter `class_id` + `classesForPromote` dropdown + `students/index.blade.php` dengan checkbox + form bulk promote + filter kelas
- **Verifikasi:** Manual migrate:fresh + seed → promote 2 siswa ke kelas target → count kelas berubah.

---

## Design.md — Website Profesional untuk Sekolah

> **Tujuan:** Membuat website sekolahan yang professional, ramah pengguna (guru & semua usia), responsif, dark/light toggle, load cepat, dengan skeleton loading.

### Goal
Mendesain website sekolahan modern dengan TailwindCSS, dukungan dark mode, responsive mobile-first, dan performa tinggi.

### Current Context / Assumptions
- Project menggunakan Laravel 12 + Breeze + Tailwind v4
- Sudah ada Blade views di `resources/views/layouts/app.blade.php` dengan class `glass` dan token CSS root
- Navigasinya sudah ada di `resources/views/layouts/navigation.blade.php`
- Tidak ada design system yang terstruktur

### Architecture / Proposed Approach
- Gunakan **TailwindCSS v4** dengan konfigurasi `darkMode: 'class'` untuk toggle manual dark/light.
- Definisikan **Design Tokens** di `tailwind.config.js` (warna, radius, blur) dan `resources/css/app.css`.
- Layout utama: `app.blade.php` dengan `layouts.navigation` + `@yield('content')`.
- Setiap halaman (dashboard, classes, students, point-ledgers) pakai `x-app-layout` dengan `x-slot name="header"`.
- Gunakan **skeleton UI** saat data masih load (`@empty` state).
- Semua warna & radius sudah didefinisikan sebagai CSS variable di `:root` untuk mudah theming.

### Step-by-step Tasks (Design.md only — not code execution)

#### Task 1 — Setup Tailwind v4 & Design Tokens
**File:** `tailwind.config.js` (buat jika belum ada)
```js
/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class', // toggle manual via <html class="dark"> atau JS
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    extend: {
      colors: {
        school: {
          navy: '#1e3a5f',
          // warna lainnya...
        },
      },
      borderRadius: {
        lg: '20px',
        md: '14px',
      },
      blur: {
        sm: '12px',
      },
    },
  },
};
```
**Perintah:** `npm install -D tailwindcss@4` (jika belum) lalu `npx tailwindcss init -p`.

#### Task 2 — Tambah CSS variable & dark mode toggle di `resources/views/layouts/app.blade.php`
```html
<html class="{{ request()->hasBeenVerified() ? 'dark' : '' }}">

<!-- di header nav, tambah tombol toggle -->
<div class="hidden sm:flex items-center gap-2">
  <button id="themeToggle" class="px-3 py-1 rounded-md bg-gray-100 text-sm text-gray-700 hover:bg-gray-200 transition-colors">
    <svg id="moonIcon" class="h-4 w-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
      <path d="M21 12.79A9 9 0 1111.21 3 9 9 0 0121 12.79zM11 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S15.52 2 11 2zm0 2c-1.66 0-3.05-.65-4.22-1.75A5.5 5.5 0 015 12.09 5.5 5.5 0 015 2c0-1.66.8-3.05 2.18-4.22A13.5 13.5 0 006.75 4.5c1.62 0 3.06.5 4.28.88l-.56 1.53c-.07.22-.07.45 0 .67A4.5 4.5 0 0111 8c0 .83-.13 1.63-.35 2.32z"/>
    </svg>
    <span id="moonText" class="ml-1">Dunia</span>
    <svg id="sunIcon" class="h-4 w-4 inline-block hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
      <path d="M12 2v4l10 8-10 8v-4H6v8l10 8v-4H3.14l1.63-11.32A4.5 4.5 0 0112 2c1.66 0 3 .55 4.25 1.31l.57-1.52c.07-.22.07-.45 0-.67zM2 12a10 10 0 1120 0 10 10 0 01-20zm0 2c3.31 0 6.34.84 8.94 2.06a5.5 5.5 0 012.06 5.89l-.15.42a5.5 5.5 0 01-5.71-2.06A13.5 13.5 0 0112 22c-3.31 0-6.34-.84-8.94-2.06z"/>
    </svg>
  </button>
</div>
```
Tambah script di bawah `</html>`:
```html
<script>
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const html = document.documentElement;
  const moonIcon = document.getElementById('moonIcon');
  const sunIcon = document.getElementById('sunIcon');
  const moonText = document.getElementById('moonText');

  function setDark(mode) {
    html.classList.toggle('dark', mode);
    moonIcon.classList.toggle('hidden', !mode);
    sunIcon.classList.toggle('hidden', mode);
    moonText.textContent = mode ? 'Terang' : 'Gelap';
  }

  // saved preference
  const saved = localStorage.getItem('theme');
  setDark(saved === 'dark');

  // toggle
  document.getElementById('themeToggle').addEventListener('click', () => {
    setDark(!html.classList.contains('dark'));
    localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
  });

  // fallback ke preferensi sistem
  if (!saved) setDark(prefersDark);
</script>
```

#### Task 3 — Definisikan warna & typografi di `resources/css/app.css`
```css
@tailwind base;
@tailwind components;
@tailwind utilities;

:root {
  --bg: #ffffff;
  --bg-muted: #f8f9fa;
  --text: #1e3a5f;
  --text-secondary: #64748b;
  --border: #e2e8f0;
  --glass-bg: rgba(255, 255, 255, 0.72);
  --glass-border: rgba(255, 255, 255, 0.55);
  --glass-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
  
  /* Dark mode */
}
.dark {
  --bg: #0a0a0a;
  --bg-muted: #161615;
  --text: #f8fafc;
  --text-secondary: #a0aec0;
  --border: #334155;
  --glass-bg: rgba(15, 23, 42, 0.72);
  --glass-border: rgba(15, 23, 42, 0.4);
  --glass-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
}
```
Tambah `@layer utilities` untuk komponen khusus (glass card, tombol, dsb).

#### Task 4 — Skeleton loading di halaman utama
**File:** `resources/views/dashboard.blade.php` — tambah `@if(empty($totalStudentsActive))` skeleton:
```html
<div class="glass r-lg p-4 animate-pulse bg-gray-100/50 dark:bg-gray-900/50">
  <p class="text-xs text-gray-500 uppercase tracking-wide">Total Siswa Aktif</p>
  <p class="mt-1 text-2xl font-semibold text-gray-900">…</p>
</div>
```
Sama untuk kartu lain.

#### Task 5 — Gambar desain (visual)
- **Mobile:** Nav menu collapsed di bawah 640px, tombol toggle di header kanan.
- **Desktop:** Nav horizontal, breadcrumb di atas konten.
- **Glass effect:** `backdrop-filter: blur(14px)` + `rgba(255,255,255,0.72)` + border `rgba(255,255,255,0.55)`.
- **Font:** `figtree` (semi-bold untuk header, regular untuk body) — atau fallback `Inter`.

#### Task 6 — Cepat load & Performance
- Gunakan `lazy-load` untuk gambar (jika ada).
- Paginasi 20 konsisten (sudah ada di project).
- Hindari query N+1 (sudah diperbaiki di `withCount`, `lockForUpdate`).
- Vite build sudah di‑produksi (`npm run build`).

### Risks, Tradeoffs, Open Questions
- **Dark mode toggle:** User harus manually klik; tidak otomatis sesuai sistem (kira-kira `prefers-color-shape` sudah ditangani di script).
- **Tailwind v4:** Masih baru; pastikan semua `@apply` dan klaser sudah kompatibel. Saat ini pakai `@tailwind base/components/utilities` saja.
- **Glass effect di mobile:** blur mungkin terlalu kuat pada layar kecil — bisa pakai `sm:blur-14px`.
- **Font `figtree`:** Butuh `@import` dari Google Fonts atau host sendiri; kalau unavailable, fallback ke `Inter` atau sistem font.

### Design.md Summary
Website sekolahan modern: Tailwind v4 + `darkMode: 'class'` + CSS variable di `:root` + toggle tombol di header + skeleton loading + glass card effect + responsif mobile-first + load cepat (paginasi 20, hindari N+1). File simpan di `.hermes/plans/`.

---

## Final Summary (What's Done)
1. ✅ Balance fix: `DB::transaction` + `lockForUpdate` + pengurangan di luar `??` → `balance_after` 500 (verifikasi tinker)
2. ✅ Source label: `PointLedger::sourceLabel()` → `KAS-2026-0001` (verifikasi tinker)
3. ✅ Naik kelas massal: Route + Controller + View (classes/index + students/index) → promote 2 siswa berhasil
4. ✅ Design.md: Tailwind v4 + dark mode toggle + CSS variable + skeleton + glass effect + specs lengkap

All code verified; design plan ready for implementation.