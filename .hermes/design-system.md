---
version: alpha
name: Pelanggaran Siswa
description: Website profesional sekolahan — ramah guru, ramah semua usia, responsif mobile-first, dark/light, glass morphism ringan.
colors:
  primary: "#1e3a5f"
  secondary: "#64748b"
  tertiary: "#059669"
  accent: "#f59e0b"
  neutral: "#ffffff"
  surface: "#f8fafc"
  border: "#e2e8f0"
  danger: "#dc2626"
  on-primary: "#ffffff"
  on-tertiary: "#ffffff"
  on-accent: "#000000"
  on-surface: "#1e293b"
  on-surface-muted: "#64748b"
  dark-bg: "#111114"
  dark-surface: "#1c1c1f"
  dark-surface-raised: "#252830"
  dark-text: "#e8eaed"
  dark-text-muted: "#8b919a"
  dark-border: "#252830"
  dark-glass-bg: "rgba(30, 32, 40, 0.78)"
  dark-glass-border: "rgba(255, 255, 255, 0.08)"
  dark-glass-shadow: "rgba(0, 0, 0, 0.4)"
  glass-bg: "rgba(255, 255, 255, 0.72)"
  glass-border: "rgba(255, 255, 255, 0.55)"
  glass-shadow: "rgba(15, 23, 42, 0.06)"
typography:
  h1:
    fontFamily: Figtree
    fontSize: 2.25rem
    fontWeight: 700
    lineHeight: 1.15
    letterSpacing: "-0.01em"
  h2:
    fontFamily: Figtree
    fontSize: 1.5rem
    fontWeight: 600
    lineHeight: 1.25
  h3:
    fontFamily: Figtree
    fontSize: 1.125rem
    fontWeight: 600
    lineHeight: 1.35
  body-lg:
    fontFamily: Figtree
    fontSize: 1.125rem
    lineHeight: 1.6
  body-md:
    fontFamily: Figtree
    fontSize: 1rem
    lineHeight: 1.6
  body-sm:
    fontFamily: Figtree
    fontSize: 0.875rem
    lineHeight: 1.5
  label:
    fontFamily: Figtree
    fontSize: 0.75rem
    fontWeight: 600
    letterSpacing: "0.06em"
    lineHeight: 1.4
  mono:
    fontFamily: ui-monospace
    fontSize: 0.8125rem
    lineHeight: 1.5
rounded:
  sm: 8px
  md: 12px
  lg: 20px
  full: 9999px
spacing:
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 40px
  "2xl": 64px
elevation:
  sm: "0 1px 2px rgba(15,23,42,0.06)"
  md: "0 4px 12px rgba(15,23,42,0.08)"
  lg: "0 8px 24px rgba(15,23,42,0.10)"
  glass: "0 4px 16px rgba(15,23,42,0.06)"
  glass-dark: "0 4px 16px rgba(0,0,0,0.4)"
components:
  button-primary:
    backgroundColor: "{colors.tertiary}"
    textColor: "{colors.on-tertiary}"
    rounded: "{rounded.sm}"
    padding: "12px 20px"
  button-primary-hover:
    backgroundColor: "#047857"
    textColor: "{colors.on-tertiary}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.on-surface}"
    rounded: "{rounded.sm}"
    padding: "12px 20px"
  button-secondary-hover:
    backgroundColor: "{colors.border}"
  button-danger:
    backgroundColor: "{colors.danger}"
    textColor: "#ffffff"
    rounded: "{rounded.sm}"
    padding: "12px 20px"
  card:
    backgroundColor: "{colors.neutral}"
    textColor: "{colors.on-surface}"
    rounded: "{rounded.lg}"
    padding: "{spacing.lg}"
  card-glass:
    backgroundColor: "{colors.glass-bg}"
    textColor: "{colors.on-surface}"
    rounded: "{rounded.lg}"
    padding: "{spacing.md}"
  badge-success:
    backgroundColor: "#dcfce7"
    textColor: "#166534"
    rounded: "{rounded.full}"
    padding: "4px 10px"
  badge-warning:
    backgroundColor: "#fef3c7"
    textColor: "#92400e"
    rounded: "{rounded.full}"
    padding: "4px 10px"
  badge-danger:
    backgroundColor: "#fee2e2"
    textColor: "#991b1b"
    rounded: "{rounded.full}"
    padding: "4px 10px"
  input:
    backgroundColor: "{colors.neutral}"
    textColor: "{colors.on-surface}"
    rounded: "{rounded.sm}"
    padding: "10px 14px"
  skeleton:
    backgroundColor: "#e2e8f0"
    rounded: "{rounded.sm}"
---

## Overview

Sistem informasi pelanggaran dan pencapaian siswa SMK. Visual identity harus
terasa **terpercaya, bersih, dan mudah dipakai guru yang tidak familiar
dengan teknologi** — termasuk pengguna usia lanjut.

Warna utama biru gelap profesional (navy) dengan hijau untuk aksi positif,
kuning untuk peringatan, merah untuk bahaya. Setiap warna berfungsi — tidak
dekoratif. Glass morphism ringan pada kartu untuk kedalaman tanpa
disturb.

Toggle dark/light disediakan untuk kenyamanan membaca di berbagai kondisi
cahaya, bukan sekadar estetika.

## Colors

- **Primary ({colors.primary}):** Nama sekolah, heading halaman, sidebar
  background. Gunakan untuk elemen identitas — logo, judul, breadcrumb.
- **Secondary ({colors.secondary}):** Teks pendukung, metadata, label
  keterangan, border ringan. Tidak pernah jadi warna aksi.
- **Tertiary ({colors.tertiary}):** Tombol utama, aksi positif (Simpan,
  Validasi, Naik Kelas). Satu-satunya warna interaksi tinggi.
- **Accent ({colors.accent}):** Badge peringatan, angka menonjol, highlight
  ringan. Gunakan hemat — hanya untuk menarik perhatian sesaat.
- **Danger ({colors.danger}):** Tombol hapus, badge pelanggaran berat,
  pesan error, saldo poin kritis (<500).
- **Surface ({colors.surface}):** Background halaman. Putih bersih di light,
  hampir hitam di dark.
- **Neutral ({colors.neutral}):** Background kartu, input field, nav.
- **Border ({colors.border}):** Garis pemisah, garis input, card outline.
- **Glass variants:** `glass-bg`, `glass-border`, `glass-shadow` — untuk
  efek frosted pada kartu dan header. Jangan dipakai di elemen interaktif
  tanpa fallback solid.

### Dark Mode

Dark mode pakai palet terpisah (`dark-bg`, `dark-surface`, `dark-text`,
dsb.). Jangan ubah hex light mode — dark mode punya token sendiri.

Dark mode aktif via `<html class="dark">` atau tombol toggle. Prefers-color-scheme
sebagai fallback awal, localStorage sebagai persistensi.

## Typography

Figtree untuk semua. Family tunggal menjaga konsistensi dan mengurangi
layout shift dari font loading.

- **h1 ({typography.h1}):** Judul halaman — Dashboard, Daftar Siswa.
  Tegas tapi tidak agresif. Letter-spacing negatif ringan agar terasa solid.
- **h2 ({typography.h2}):** Judul section — Kasus Terbaru, Siswa Poin
  Rendah.
- **h3 ({typography.h3}):** Judul kartu, sub-section.
- **body-md ({typography.body-md}):** Teks utama. Line-height 1.6 untuk
  keterbacaan tinggi — penting untuk guru dengan penglihatan berkurang.
- **body-sm ({typography.body-sm}):** Teks sekunder, caption, timestamp.
- **label ({typography.label}):** Label input, badge text, tombol.
  Uppercase dengan letter-spacing lebar untuk keterbacaan di ukuran kecil.
- **mono ({typography.mono}):** Nomor kasus (KAS-2026-0001), NIS, kode
  kategori. Monospace agar alignment tetap lurus.

### Ukuran font minimum
Body text tidak boleh lebih kecil dari `0.875rem` (14px) di cualquier kondisi.
Label caps minimum `0.75rem` (12px).

## Layout

Spacing scale 4px basis. `md` (16px) gap antar elemen dalam kartu,
`lg` (24px) gap antar kartu, `xl` (40px) section break.

### Breakpoints
- Mobile-first. Konten utama full-width di bawah 640px.
- `sm` (640px): Nav horizontal muncul, toggle theme terlihat.
- `md` (768px): Grid kartu 2 kolom.
- `lg` (1024px): Max-width 788px content area, sidebar collapsed.
- `xl` (1280px): Full layout dengan max-w-7xl.

### Page structure
```
[Header - sticky, glass bg]
  [Logo] [Nav links] [Search] [Theme toggle] [User dropdown]
[Main content - max-w-7xl mx-auto px-4]
  [Breadcrumb] (optional)
  [Page title - x-slot header]
  [Grid of cards]
  [Table / list]
  [Pagination]
```

## Elevation & Depth

Tiga level elevasi, bukan empat — menjaga kesederhanaan.

- **sm ({elevation.sm}):** Tombol, input field. Lifted halus.
- **md ({elevation.md}):** Kartu statis, dropdown.
- **lg ({elevation.lg}):** Modal, toast notification.
- **glass ({elevation.glass}):** Kartu di atas gradient/image.
  `backdrop-filter: blur(14px)` + `glass-bg` + `glass-border`.
  Jangan pakai di mobile tanpa `-webkit-` prefix.

Dark mode: glass shadow pakai `glass-dark` (lebih gelap, opacity lebih tinggi).

## Shapes

Rounded corners konsisten — tidak ada mixed radius dalam satu kartu.

- **sm ({rounded.sm}):** Tombol, input, badge. Interactive elements.
- **md ({rounded.md}):** Toast, tooltip, dropdown item.
- **lg ({rounded.lg}):** Kartu utama, modal container. Lebih besar untuk
  kesan modern tanpa terlalu rounded.
- **full ({rounded.full}):** Avatar, pill badge, status dot.

## Components

- **button-primary:** Satu tombol aksi tinggi per halaman (Simpan, Naik
  Kelas, Validasi). Hijau. Hover gelap 1 langkah.
- **button-secondary:** Aksi sekunder (Batal, Export, Filter). Neutral.
  Border tipis agar tidak hilang.
- **button-danger:** Hapus, Tolak, Buang kasus. Merah. Selalu pakai
  `confirm()` sebelum submit — tidak ada aksi destructive tanpa konfirmasi.
- **card:** Surface default untuk konten terkelompok. Putih solid, rounded-lg,
  padding lg. Tidak ada shadow bawaan — shadow hanya saat hover atau elevated.
- **card-glass:** Untuk kartu statistik di dashboard, header, atau di atas
  background gradient. Transparan dengan blur.
- **badge-success/warning/danger:** Status indicator. Pill-shaped.
  Tidak boleh lebih dari 1 badge per baris di tabel — pilih yang paling penting.
- **input:** Text field, select, textarea. Border abu-abu, focus ring emerald.
  Placeholder text secondary color.
- **skeleton:** Loading placeholder. `animate-pulse` dengan warna
  surface-raised. Muncul saat data belum load — bukan layar kosong.
  Setiap kartu harus punya skeleton state.

### Dark mode component adaptation
Semua komponen punya variant dark mode via token reference. Dark bg pakai
`dark-bg` / `dark-surface`, text pakai `dark-text`, border pakai
`dark-border`. Glass component pakai `dark-glass-bg` + `dark-glass-shadow`.

## Do's and Don'ts

- **Do** pakai token references (`{colors.tertiary}`) bukan hex literal di
  component definitions.
- **Do** skeleton di semua halaman — user harus melihat structure halaman
  sebelum data muncul, bukan layar kosong.
- **Do** `confirm()` di setiap tombol destructive (hapus, buang, validasi
  poin negatif). Tidak ada aksi irreversible tanpa konfirmasi.
- **Do** label text-only — tidak ada emoji atau ikon di label tombol, badge,
  atau heading. Profesional.
- **Don't** pakai warna di luar palet — extend palette dulu.
- **Don't** nested component variants — `button-primary-hover` sibling, bukan
  child.
- **Don't** font lebih kecil dari 14px (body) atau 12px (label).
- **Don't** glass effect di elemen interaktif (tombol, input) tanpa solid
  fallback — blur bisa mengganggu keterbacaan.
- **Don't** animation lebih dari 200ms untuk transisi biasa. Loading skeleton
  boleh `animate-pulse` tapi jangan spin/slide tanpa tujuan.
