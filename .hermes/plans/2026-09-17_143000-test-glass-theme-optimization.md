# Test Glass Theme Optimization Plan

> **For Hermes:** Use subagent-driven-development skill to implement this plan task-by-task.

## Goal
Maksimalkan tampilan halaman test di `resources/views/test-glass-theme.blade.php` menjadi profesional, fast-loading, mobile-friendly, dan tidak terlalu AI-looking. Akan digunakan sebagai uji coba utama (uji/pacuan utama).

## Current Context
- File: `resources/views/test-glass-theme.blade.php` (980 lines)
- Stack: Tailwind CSS v3, Alpine.js 3
- Tujuan: Halaman uji komponen glassmorphism yang akan dijadikan referensi tampilan utama
- Masalah: Terlalu banyak animasi blob, bisa lag di mobile, terlihat seperti generative AI

## Assumptions
1. Project menggunakan Laravel 11+ dengan Tailwind v3
2. Alpine.js untuk interaktivitas
3. Tujuan akhir: tampilan yang clean, institutional, dan performa baik
4. User ingin hasil maksimal karena akan digunakan sebagai referensi/pacuan

## Proposed Approach

### Visual Direction (Anti-AI)
- **Warna**: Palet institutional yang jernih, kurangi gradient yang terlalu "neon"
- **Background**: Ganti mesh blob animasi dengan pattern subtle atau warna solid
- **Glass effects**: Kurangi blur intensity di mobile, tetaplah di desktop
- **Typography**: Figtree tetap pakai, tapi skorkan hierarki font
- **Interaksi**: Toggle-simple, hover yang jelas tapi tidak berlebihan

### Performance Targets
- **Mobile**: Blur reduction from 18px → 8px, blob animation dihentikan atau di-reduce drastically
- **Load time**: Kurangi CSS/JS yang blokir render
- **CLS**: Stabilkan layout elements
- **GPU**: Kurangi will-change dan animated elements yang berlebihan

## Step-by-Step Plan

### Task 1: Reduce mesh blob complexity for mobile
**Objective**: Kurangi beban grafis di mobile devices
**Files**: `resources/views/test-glass-theme.blade.php`
**Step 1**: Kurangi ukuran blob dari 520px/620px/420px menjadi 200px/250px di mobile
**Step 2**: Matikan animation `drift` di layar kecil, hanya tampil statis
**Step 3**: Tambahkan `prefers-reduced-motion` support

**Verification**: Test di browser mobile simulator, pastikan blob tidak bergerak atau bergerak sangat lemah

### Task 2: Optimize glass blur values
**Objective**: Balance antara visual kualitas dan performa
**Files**: `resources/views/test-glass-theme.blade.php` lines 19-60
**Step 1**: Ubah `--blur-px` default dari 18px menjadi 12px (desktop)
**Step 2**: Tambahkan media query `max-width: 640px` untuk blur 8px
**Step 3**: Kurangi `--glass-shadow` di mobile

**Verification**: Lihat visual apakah glass masih rapi tapi lebih ringan

### Task 3: Simpan toggles dan interaksi
**Objective**: Toggle senter (lamp) tetap kerja tapi visual lebih minimal
**Files**: `resources/views/test-glass-theme.blade.php` lines 868-874
**Step 1**: Pastikan toggle senter tetap functional
**Step 2**: Kurangi opacity/shadow di sekitar toggle saat aktif
**Step 3**: Gunakan icon yang lebih minimal (bukan lamp glow effect)

**Verification**: Test toggle on/off, pastikan masih work dan tampilan tetap clean

### Task 4: Warna palette institutional
**Objective**: Kurangi "AI-looking" features (gradient blob, wave divider, dll)
**Files**: `resources/views/test-glass-theme.blade.php` lines 29-59
**Step 1**: Ganti warna `--page-bg` dari linear gradient menjadi single warna atau pattern subtle
**Step 2**: Kurangi warna accent dari `#7c3aed` ke warna yang lebih sober
**Step 3**: Tambahkan warna warna tetap (solid) untuk background section

**Verification**: Lihat keseluruhan, pastikan terlihat profesional bukan generatif AI

### Task 5: Mobile-first CSS adjustments
**Objective**: Pastikan fast loading dan tidak lag di mobile
**Files**: `resources/views/test-glass-theme.blade.php` lines 166-174
**Step 1**: Pastikan media query sudah aktif untuk mobile
**Step 2**: Kurangi transition duration dari 0.25s ke 0.15s di mobile
**Step 3**: Hilangkan `will-change` di element yang tidak perlu di-mobile

**Verification**: Test scroll dan interaksi di mobile simulator

## Files Likely to Change
- `resources/views/test-glass-theme.blade.php` - utama (980 lines)
- `tailwind.config.js` - jika perlu adjust config
- `resources/css/app.css` - Tailwind buildup

## Risks & Tradeoffs
1. **Over-simplifikasi**: Jangan sampai tampil kaku/seperti halaman login bawaan Laravel
2. **Performance vs Visual**: Kurang blur bisa membuat tampil lebih "cheap", butuh balance
3. **Mobile-first**: Pastikan desktop tetap menarik, mobile tetap kerja
4. **Anti-AI flags**: Hindari blob shapes, gradient bg, hero sliders, Font Awesome, wave dividers, typewriter animations, emoji icons (seperti yang diuseriz skill ui-ux-pro-max)

## Open Questions
- User menginginkan warna spesifik atau boleh pakai standar institutional?
- Toggle senter (lamp) harus tetep nyala/mati atau hanya sebagai indikator?
- Apakah ada komponen tertentu dari test glass theme yang wajib dijaga absolute?

## Success Criteria
1. ✅ Halaman tampil faster di mobile (kurangi 40-50% animation load)
2. ✅ Tidak terlihat seperti generative AI artwork
3. ✅ Toggle senter tetap functional
4. ✅ Glassmorphism still visible tapi lebih ringan
5. ✅ Tidak ada horizontal scroll di mobile
6. ✅ CLS score stabil di bawah 0.1

---
*Plan disimpan untuk eksekusi selanjutnya. Gunakan subagent-driven-development untuk implementasi task per-task.*