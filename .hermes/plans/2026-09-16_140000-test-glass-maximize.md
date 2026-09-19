# Test Glass Theme Maximize — Implementation Plan

> **For Hermes:** Use subagent-driven-development skill to implement this plan task-by-task.

**Goal:** Maksimalkan `resources/views/test-glass-theme.blade.php` jadi showcase/pacuan utama — fast di mobile, ga lag, tidak terlalu AI, tetap glass premium.

**Architecture:** Single Blade `test-glass-theme.blade.php` standalone (tanpa `x-app-layout`), Alpine.js 3 + Tailwind 3. Optimasi via CSS token tuning, mesh blob simplification, blur budget, motion reduction, dan mobile-first responsive fixes. Tidak ganti stack.

**Tech Stack:** Laravel Blade, Tailwind CSS 3, Alpine.js 3, Vite, Figtree (bunny.net)

---

## Konteks Saat Ini

**File:** `resources/views/test-glass-theme.blade.php` — 980 baris, 76KB, single-file Alpine `glassLab()`.

**Route:** `GET /test → view('test-glass-theme')` di `routes/web.php:16-18` (outside auth, public).

**Stack build:** `vite.config.js` input `resources/css/app.css + resources/js/app.js`, `tailwind.config.js` content `resources/views/**/*.blade.php`.

**Yang sudah ada & bagus:**
- Token CSS `:root` (`--blur-px`, `--radius-*`, `--glass-*`, `--accent` dll) — bisa di-tweak live
- Touch detection `document.documentElement.classList.add('touch-device')` + `@media (max-width:640px)` blur reduction
- `prefers-reduced-motion` + `@supports not (backdrop-filter)` fallback
- `will-change` + `contain: layout style` hints

**Masalah utama (kenapa lag & keliatan AI):**
1. **Mesh blobs berat:** 3 blobs 520/620/420px, `filter: blur(90px)`, `animation: drift 22s`, `radial-gradient` + `saturate(180-200%)` + `backdrop-filter: blur(18px)` di banyak `.glass` — GPU killer di HP mid-range, trigger repaint terus.
2. **AI flags:** `bg-candy` / `bg-sunset` / `bg-ocean` gradient blobs + spring `cubic-bezier(0.34, 1.56, 0.64, 1)` di `.press` + `.qs-tile` + pill→rounded morph — ciri khas generative AI glass.
3. **Blur budget tinggi:** `--blur-px: 18px` + `calc(+12px)` di `.glass-strong` = 30px blur, `saturate(200%)` — mahal di mobile.
4. **Single Alpine component raksasa:** `glassLab()` handle 8 section (tombol/chip/form/qs/tabel/modal/tab/acc/extra) + watchers `blur/radius` setProperty tiap keystroke + `scroll` spy tanpa throttle.
5. **Hover revert brittle:** `.touch-device *:hover { revert !important }` — tidak cover `group-hover:scale-*`, specificity tinggi, bisa bocor.
6. **Aksesibilitas & CLS:** switch `div[role=switch]` tanpa `aria-label` jelas, range slider tanpa `aria-valuenow`, section `sticky top-[76px]` rawan CLS di mobile.

---

## Apa itu Toggle Senter? (Penjelasan)

**Lokasi:** `resources/views/test-glass-theme.blade.php:871` — array `qs`:
```js
{id:5, label:'Senter', on:false, icon:ICONS.lamp}
```

**Maksud:** Meniru **Quick Settings Tiles Android** (Wi-Fi, Bluetooth, Pesawat, Diam/Silent, Senter/Flashlight, Notifikasi). "Senter" = **Flashlight** — ikon `lamp` (SVG lampu). Di Android, tile senter nyalain flash LED HP. Di Glass Lab ini **hanya demo visual**: klik → `q.on = !q.on` → class toggle `qs-off` (pill, muted) ↔ `qs-on` (rounded, `background: var(--accent)`, shadow).

**Kenapa bingungin:** Tidak ada efek samping (layar tidak jadi terang, tidak ada `brightness` overlay). User ekspektasi senter = layar putih/terang, tapi di sini cuma ganti warna tile. Makanya terasa "maksudnya apa?".

**Opsi untuk plan (pilih 1, butuh keputusanmu):**
- **A. Keep demo (rekomendasi pacuan):** Biarkan sebagai tile QS biasa, ganti label jadi `Lampu` + tooltip `Demo — tidak nyalakan flash sungguhan`. Paling aman, tidak nambah logic.
- **B. Jadikan functional:** Saat `Senter=ON` → overlay fullscreen `background: rgba(255,255,255,0.92)` + `brightness` naik 100% + `body overflow hidden` → efek senter beneran. Keren untuk showcase, tapi nambah state & perlu ESC handling.
- **C. Hapus:** Ganti jadi `Hotspot` / `Lokasi` yang lebih relevan untuk app pelanggaran siswa. Konsisten dengan konteks sekolah.

**Rekomendasi:** **A untuk plan awal**, B bisa sebagai `Task 9 — Optional`.

---

## Design Direction — Biar Tidak Terlalu AI (Anti-Pattern Fix)

Ambil dari `ui-ux-pro-max` + `responsive-web` rules:

**1. Color — dari Candy ke Institutional Glass:**
- Accent sekarang `#7c3aed` (violet neon) → ganti ke **slate/indigo sober** `#3b5bdb` / `#1e3a5f` (navy institusi) + accent muted `#0ea5e9`. Hilangkan `bg-candy` default, jadikan `bg-plain` / `bg-ocean` yang lebih kalem sebagai default.
- `--page-bg: linear-gradient(135deg, #e8e0f0 ...)` → `background: #f1f5f9` solid + subtle dot grid `background-image: radial-gradient(...)` opacity 0.04. Hapus 3 blobs atau sisakan 1 blob static blur 60px opacity 0.18.
- Status colors tetap (`--success/warning/danger`) tapi soft-nya turun 0.14→0.10 biar tidak neon.

**2. Radius & Blur — kurangi "bubbly":**
- `--radius-lg: 28px` → `20px`, `--radius-md: 20px` → `14px`. `pill` hanya untuk chip/segment, card pakai `r-md`.
- `--blur-px: 18px` → `14px` desktop, `10px` mobile. `saturate(180%)` → `saturate(140%)`. `.glass-strong` dari `calc(+12px)` → `+6px`.

**3. Motion — hilangkan spring AI:**
- `cubic-bezier(0.34, 1.56, 0.64, 1)` (bounce) → `cubic-bezier(0.2, 0, 0, 1)` atau `ease-out` 180-220ms. `drift 22s` → `drift 40s` atau `animation: none` di mobile (sudah ada, perkuat).
- Tambah `prefers-reduced-motion` yang sudah ada, tapi extend ke `scroll-behavior`.

**4. Typography — tetap Figtree, tapi hierarchy:**
- Hero `text-3xl sm:text-5xl` → `text-[28px] sm:text-[36px]` (kurangi hero syndrome), sub `text-sm sm:text-lg` → `text-sm` dengan `max-w-xl`.
- Label section `text-xs uppercase tracking-widest` keep, tapi `font-bold` → `font-semibold`.

**5. Components — dari playful ke institutional:**
- `qs-on` shadow `0 4px 20px rgba(124,58,237,0.35)` → `0 2px 10px rgba(0,0,0,0.08)`.
- Table header `th-sort` keep, tapi pagination `pill` → `rounded-lg`.

---

## Performance Budget — Fast Mobile, Ga Lag

**Target:** Lighthouse Performance ≥90 di Moto G4, CLS <0.1, no jank scroll.

**Strategi:**
- **Blur budget:** Max 2 backdrop-filter layers visible di viewport. `.glass-subtle` di mobile fallback ke `background: rgba(255,255,255,0.88)` tanpa blur via `@supports` / media query.
- **Blob budget:** Desktop 1 blob, mobile 0-1 blob static (no animation). `filter: blur(90px)` → `60px`.
- **Containment:** `contain: layout paint` + `content-visibility: auto` untuk section di bawah fold (`#overlay`, `#navigasi`, `#extra`).
- **JS:** Throttle `scroll` spy `requestAnimationFrame`, debounce `blur/radius` watcher, lazy `filtered()` sort (cache).
- **Fonts:** `font-display: swap` sudah dari bunny.net, tambah `preconnect` keep, tidak load weight 800 jika tidak dipakai.

---

## Step-by-Step Plan

### Task 1: Audit & Baseline (read-only)

**Objective:** Ukur kondisi sebelum ubah, jadi bisa klaim "lebih fast".

**Files:** Read `test-glass-theme.blade.php`, `package.json`, `resources/css/app.css`

**Step 1: Capture baseline**
```bash
php -l resources/views/test-glass-theme.blade.php  # cek syntax (blade via php -l tidak valid, pakai vite build)
npm run build 2>&1 | tail -20
```

**Step 2: Catat metrik manual**
- Buka `/test` di Chrome DevTools → Performance tab → record scroll 5 detik → lihat GPU / long tasks
- Lighthouse mobile → catat Performance, CLS, Speed Index

**Step 3: Commit baseline tidak perlu** — hanya dokumentasi di plan.

**Verification:** Punya angka before (misal Performance 68, CLS 0.15) untuk banding after.

---

### Task 2: Token Redesign — Palet & Radius Anti-AI

**Objective:** Ganti candy neon jadi institutional glass.

**Files:** Modify `resources/views/test-glass-theme.blade.php:18-60`

**Step 1: Ganti :root tokens**
```css
:root {
  --blur-px: 14px;
  --radius-lg: 20px;
  --radius-md: 14px;
  --glass-bg: rgba(255,255,255,0.72);
  --glass-bg-strong: rgba(255,255,255,0.88);
  --glass-shadow: 0 4px 16px rgba(15,23,42,0.06);
  --glass-shadow-lg: 0 8px 28px rgba(15,23,42,0.08);
  --accent: #1e3a5f; /* navy institusi */
  --accent-soft: rgba(30,58,95,0.10);
  --page-bg: #f1f5f9; /* solid, bukan gradient */
}
.dark {
  --accent: #60a5fa;
  --page-bg: #0f172a;
}
```

**Step 2: Default bg**
```js
bg: 'plain', // was 'candy'
bgs: [{v:'plain',l:'Polos'},{v:'ocean',l:'Ocean'},{v:'candy',l:'Candy'}] // plain first
```

**Step 3: Update hero blobs CSS**
```css
.mesh-bg { background: var(--page-bg); }
.mesh-bg::before {
  content:""; position:absolute; inset:0;
  background-image: radial-gradient(rgba(15,23,42,0.04) 1px, transparent 1px);
  background-size: 24px 24px;
}
```

**Step 4: Verify**
```bash
npm run build
```
Expected: Build PASS, visual di `/test` jadi lebih kalem, tidak neon.

**Step 5: Commit**
```bash
git add resources/views/test-glass-theme.blade.php
git commit -m "feat(test): sober institutional tokens, plain default bg"
```

---

### Task 3: Mesh Blob Diet — 3→1 Blob, No Animation Mobile

**Objective:** Hilangkan GPU killer.

**Files:** Modify `resources/views/test-glass-theme.blade.php:88-104, 166-174`

**Step 1: HTML — hapus 2 blobs**
```html
<div class="mesh-bg" aria-hidden="true">
    <div class="mesh-blob mesh-blob-1"></div>
</div>
```

**Step 2: CSS — single blob, subtle**
```css
.mesh-blob-1 { width: 640px; height: 640px; top: -10%; left: -8%; background: radial-gradient(circle, rgba(96,165,250,0.18) 0%, transparent 70%); filter: blur(60px); opacity: 0.5; animation: drift 40s ease-in-out infinite alternate; }
.dark .mesh-blob { opacity: 0.18; }
@media (max-width: 640px) {
  .mesh-blob { animation: none !important; filter: blur(40px); opacity: 0.18; }
  .mesh-blob-1 { width: 320px; height: 320px; }
}
```

**Step 3: Containment**
```css
.mesh-bg { contain: strict; }
.glass, .glass-strong { contain: layout paint; }
#overlay, #navigasi, #extra { content-visibility: auto; contain-intrinsic-size: 600px; }
```

**Verification:** DevTools Performance → scroll, GPU layer count turun.

---

### Task 4: Glass Blur Budget & Fallback

**Objective:** Fast blur di HP tanpa mengorbankan desktop.

**Files:** Modify `resources/views/test-glass-theme.blade.php:65-84, 181-188`

**Step 1: Kurangi saturate**
```css
.glass { backdrop-filter: blur(var(--blur-px)) saturate(140%); }
.glass-strong { backdrop-filter: blur(calc(var(--blur-px) + 6px)) saturate(150%); }
```

**Step 2: Mobile fallback — matikan blur di subtle cards**
```css
@media (max-width: 640px) {
  :root { --blur-px: 10px; }
  .glass-subtle { backdrop-filter: none; -webkit-backdrop-filter: none; background: rgba(255,255,255,0.92); }
  .dark .glass-subtle { background: rgba(20,18,40,0.92); }
}
```

**Step 3: @supports fallback sudah ada, keep.**

**Verification:** iPhone SE simulator — cards tetap glass look tapi tidak lag.

---

### Task 5: Motion Tuning — Hilangkan Spring AI

**Objective:** Transisi terasa profesional, bukan bounce AI.

**Files:** Modify `resources/views/test-glass-theme.blade.php:106-117, 145-148`

**Step 1: Ganti press**
```css
.press { transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease; }
.press:active { transform: scale(0.98); } /* was 0.96 + radius morph */
.qs-tile { transition: background-color 0.18s ease, color 0.18s ease, border-radius 0.18s ease; }
```

**Step 2: Toast**
```css
.toast-in { animation: toast-in 0.22s ease-out; }
@keyframes toast-in { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform:none; } }
```

**Step 3: Reduced motion sudah ada, tambah**
```css
@media (prefers-reduced-motion: reduce) {
  html { scroll-behavior: auto; }
}
```

**Verification:** Klik semua tombol — terasa snappy, tidak mantul.

---

### Task 6: Quick Settings — Hapus Senter, Polish Grid

**Objective:** Hapus tile Senter (keputusan user 2026-09-16), polish QS grid tersisa.

**Files:** Modify `resources/views/test-glass-theme.blade.php:471-497, 868-874, 783-790`

**Step 1: Hapus senter dari array**
```js
qs: [
  {id:1,label:'Wi-Fi',on:true,icon:ICONS.wifi},{id:2,label:'Bluetooth',on:false,icon:ICONS.bt},
  {id:3,label:'Pesawat',on:false,icon:ICONS.plane},{id:4,label:'Diam',on:true,icon:ICONS.moon},
  {id:6,label:'Notifikasi',on:true,icon:ICONS.bell}
],
// id:5 Senter dihapus — web tidak bisa toggle flash HP, hanya demo membingungkan
```

**Step 2: Hapus ICONS.lamp jika tidak dipakai**
```js
// hapus baris lamp dari ICONS const jika tidak ada referensi lain
```

**Step 3: QS tile polish — 44px min target (tetap)**
```html
<button @click="q.on=!q.on" class="qs-tile press flex flex-col items-center gap-2 py-4 px-2 cursor-pointer min-h-[88px] min-w-[88px]" :class="q.on?'qs-on':'qs-off'" :aria-pressed="q.on" :aria-label="q.label">
```

**Verification:** Grid QS 5 tile, tidak ada Senter, toggle lain tetap jalan.

---

### Task 7: Responsive & Touch — Fix Hover, Tap Target, Sticky

**Objective:** Fast mobile, no horizontal scroll, hover tidak bocor.

**Files:** Modify `resources/views/test-glass-theme.blade.php:152-165, 203-222, 273-279`

**Step 1: Fix touch-device hover (tambah group-hover override)**
```css
.touch-device *:hover { color: revert !important; background-color: revert !important; border-color: revert !important; box-shadow: revert !important; transform: revert !important; opacity: revert !important; }
.touch-device .group:hover .group-hover\:scale-105,
.touch-device .group:hover .group-hover\:scale-110 { transform: none !important; }
```

**Step 2: Sticky nav — kurangi CLS**
```css
nav.sticky { top: 12px; } /* konsisten */
@media (max-width: 640px) { nav.sticky { top: 8px; } }
```
```html
<nav class="sticky top-3 sm:top-4 z-50 ..."> <!-- keep, tapi pastikan tidak ada layout shift -->
```

**Step 3: Grid & overflow**
```html
<div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-3"> <!-- sudah ok, ensure gap-2 di <360px -->
<div class="glass-strong r-lg px-3 py-2 flex gap-1 overflow-x-auto scrollbar-none"> <!-- tambah scrollbar-none -->
```

**Step 4: Tap targets 44px**
```css
.switch { width: 48px; height: 28px; } /* was 44x24, naik ke 44+ */
.seg-btn, .qs-tile, button.press { min-height: 44px; }
```

**Verification:** Test di 375px, 768px, 1024px, 1440px — no horizontal scroll, tap target audit PASS.

---

### Task 8: A11y & Keyboard

**Objective:** Showcase yang accessible.

**Files:** Modify `resources/views/test-glass-theme.blade.php:241-260, 746-768`

**Step 1: Switch a11y**
```html
<div class="switch" role="switch" tabindex="0" :aria-checked="dark.toString()" aria-label="Mode gelap" @click="toggleDark()" @keydown.space.prevent="toggleDark()" @keydown.enter="toggleDark()"></div>
```

**Step 2: Modal focus trap**
```html
<div x-show="modal.open" x-trap.noscroll="modal.open" role="dialog" aria-modal="true" aria-labelledby="modal-title">
  <h3 id="modal-title" x-text="modalCfg().title"></h3>
</div>
```
- Butuh `alpinejs` + `@alpinejs/focus`? Jika tidak mau nambah dep, pakai `x-init="$watch('modal.open', v=> v && $nextTick(()=> $el.querySelector('button').focus()))"`.

**Step 3: Range slider aria**
```html
<input type="range" :aria-valuenow="blur" aria-valuemin="0" aria-valuemax="32" aria-label="Intensitas blur">
```

**Verification:** Tab navigation full page, ESC tutup modal, screen reader label jelas.

---

### Task 9: JS Perf — Throttle & Debounce

**Objective:** Alpine tidak jank.

**Files:** Modify `resources/views/test-glass-theme.blade.php:801-824, 897-912`

**Step 1: Scroll spy throttle**
```js
init() {
  // ...
  let ticking = false;
  const spy = () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      const ids = this.sections.map(s=>s.id);
      let cur = ids[0];
      for (const id of ids) {
        const el = document.getElementById(id);
        if (el && el.getBoundingClientRect().top < 220) cur = id;
      }
      this.activeSection = cur;
      ticking = false;
    });
  };
  window.addEventListener('scroll', spy, { passive: true });
},
```

**Step 2: Debounce blur/radius watcher**
```js
this.$watch('blur', v => {
  clearTimeout(this._blurT);
  this._blurT = setTimeout(()=> document.documentElement.style.setProperty('--blur-px', v+'px'), 16);
});
```

**Step 3: filtered() memoize — sudah ok, tapi tambah**
```js
// jika rows > 50, pertimbangkan computed + cache, untuk 8 rows current tidak perlu
```

**Verification:** Performance tab → scroll tidak trigger long tasks.

---

### Task 10: Build, Verify, Ship

**Objective:** Pastikan pacuan utama siap.

**Files:** All

**Step 1: Build**
```bash
npm run build
php artisan route:list | grep test
```

**Step 2: Manual QA checklist**
- [ ] 375px: no horizontal scroll, blur ringan, blobs static, tap target 44px
- [ ] 768px: 2-col grids ok
- [ ] 1024px+: glass blur 14px, 1 blob animasi slow 40s
- [ ] Dark toggle persist `localStorage`
- [ ] Senter tile ada label Demo, toggle jelas
- [ ] Modal ESC + backdrop click close
- [ ] Toast auto-dismiss 3.2s
- [ ] `prefers-reduced-motion: reduce` → no animation
- [ ] Lighthouse mobile Performance ≥85, CLS <0.1

**Step 3: Commit**
```bash
git add resources/views/test-glass-theme.blade.php
git commit -m "feat(test): maximize glass lab — fast mobile, sober palette, no AI flags"
git push -u origin feat/crud-optimization  # atau branch baru feat/test-glass-maximize
```

**Step 4: Demo**
- Buka `/test` di HP beneran (bukan cuma simulator), record video scroll.

---

## Files Likely to Change

- `resources/views/test-glass-theme.blade.php` — **utama, 95% perubahan di sini**
- `tailwind.config.js` — tidak perlu, tapi jika mau tambah `scrollbar-none` plugin bisa
- `resources/css/app.css` — tidak perlu (standalone Blade pakai inline `<style>`)
- `routes/web.php` — tidak perlu (route sudah benar)

## Tests / Validation

- `npm run build` — must PASS
- `php -l resources/views/test-glass-theme.blade.php` — skip (blade), ganti `php artisan view:clear`
- Manual viewport: 375 / 768 / 1024 / 1440
- Lighthouse mobile (Chrome DevTools)
- DevTools Performance → scroll jank check
- Keyboard nav: Tab, Enter, Space, ESC

## Risks & Tradeoffs

- **Terlalu plain:** Jika blobs dihapus total, kesan glass hilang → mitigasi: sisakan 1 blob subtle + dot grid.
- **Blur fallback terlalu opaque:** Mobile glass jadi solid → mitigasi: keep `backdrop-filter` di `.glass` tapi matikan di `.glass-subtle` saja.
- **Breaking Alpine:** Refactor `glassLab()` besar → mitigasi: edit incremental, test tiap task.
- **Dark mode contrast:** Navy `#1e3a5f` di dark jadi tidak kontras → pakai `#60a5fa` untuk dark accent (sudah di token).
- **Team approval:** User bilang pacuan utama → pastikan design tidak diubah sepihak tanpa approval (lesson dari `responsive-web` skill).

**Keputusan User 2026-09-16:**
1. **Senter:** HAPUS — web tidak ada hubungannya dengan flash HP, hanya demo membingungkan. Task 6 updated.
2. **Palet:** **Navy `#1e3a5f`** — institutional, tidak AI. Violet dihapus dari default.
3. **Blob:** **Hapus total (0 blob)** + dot grid tipis. Paling ringan kayak Astro/Svelte static.
4. **Scope:** **Hanya `test-glass-theme.blade.php`** = isolated `/test` saja.
5. **Target HP:** KENTANG 3GB → blur 10px, animasi mati, no blob.

**Sisa konfirmasi:** Semua terconfirmed → eksekusi Task 1-10 dimulai.

---

**Plan saved:** `.hermes/plans/2026-09-16_140000-test-glass-maximize.md`
**Next:** Jawab 5 pertanyaan di atas → eksekusi task-by-task pakai subagent (atau langsung `buatkan` jika mau auto-fix).
