# Desain Antarmuka, Sistem Tema, dan Standar Visual

## 1. Filosofi dan Prinsip Desain
Antarmuka aplikasi Pelanggaran Siswa dirancang dengan pendekatan **profesional, tenang (quiet governance), dan densitas informasi terukur**. Sistem ditujukan untuk penggunaan harian staf sekolah (Guru, Guru BK, Kesiswaan, Administrator) pada laptop/komputer kerja dan perangkat genggam responsif.

### Prinsip Utama:
1. **Zero Decorative Emoji Policy**:
   - Dilarang keras menggunakan emoji visual bawaan Unicode (seperti 📋, ⚠️, ❌, ✅) di dalam antarmuka, label tombol, tabel, maupun notifikasi. Emoji dinilai tidak formal dan menurunkan wibawa dokumen legalitas sekolah.
   - Seluruh indikator visual wajib menggunakan **Lucide Icons** (vektor SVG inline via `data-lucide` dan `lucide.createIcons()`).
2. **CSS Token-Driven Architecture**:
   - Styling utama menggunakan CSS Custom Properties (variabel `--*`) yang disuntikkan terpusat di `<style>` root `resources/views/layouts/app.blade.php`.
   - Mengurangi ketergantungan pada build tools JavaScript eksternal di halaman operasional utama (dapat berjalan stabil tanpa kompilasi ulang aset).
3. **Information Density & Anti-Fatigue**:
   - Tabel operasional mengutamakan legibilitas angka (`font-variant-numeric: tabular-nums`).
   - Form input tidak dibiarkan melebar liar pada layar monitor lebar (dibatasi melalui utility `.card-form`).
4. **Restraint & Institutional Trust**:
   - Warna aksen utama menggunakan palet biru kelembagaan institusi (Navy Institutional `#1e3a5f`), bukan warna ungu/permen cerah yang diasosiasikan dengan template generik AI.

---

## 2. Palet Warna dan Token CSS (Design Tokens)

Sistem menggunakan token semantik yang otomatis beradaptasi antara Mode Terang (*Light Mode*) dan Mode Gelap (*Dark Mode*):

```css
:root {
    /* Identitas Utama */
    --primary: #1e3a5f;             /* Navy institusi */
    --secondary: #64748b;           /* Slate pembantu */
    --tertiary: #059669;            /* Emerald aksi sukses / positif */
    --accent: #f59e0b;              /* Amber peringatan */
    --danger: #dc2626;              /* Red bahaya / debit poin */

    /* Bidang Permukaan (Light) */
    --neutral: #ffffff;             /* Latar kartu / container */
    --surface: #f8fafc;             /* Latar belakang layar */
    --border: #e2e8f0;              /* Garis pembatas kontur */
    --on-surface: #1e293b;          /* Teks utama */
    --on-surface-muted: #64748b;    /* Teks sekunder / label */

    /* Glassmorphism Aksen */
    --glass-bg: rgba(255, 255, 255, .72);
    --glass-border: rgba(255, 255, 255, .55);
    --glass-shadow: rgba(15, 23, 42, .06);

    /* Badge & Status Semantik */
    --bs: #dcfce7; --bt: #166534;    /* Success: latar & teks */
    --ws: #fef3c7; --wt: #92400e;    /* Warning: latar & teks */
    --ds: #fee2e2; --dt: #991b1b;    /* Danger: latar & teks */

    /* Radius & Spacing */
    --r-sm: 8px; --r-md: 12px; --r-lg: 20px; --r-full: 9999px;
    --sp-xs: 4px; --sp-sm: 8px; --sp-md: 16px; --sp-lg: 24px; --sp-xl: 40px; --sp-2xl: 64px;
}

.dark {
    --neutral: #1c1c1f;
    --surface: #111114;
    --border: #252830;
    --on-surface: #e8eaed;
    --on-surface-muted: #8b919a;
    --primary: #5b8abf;

    --glass-bg: rgba(30, 32, 40, .78);
    --glass-border: rgba(255, 255, 255, .08);
    --glass-shadow: rgba(0, 0, 0, .4);

    --bs: #064e3b; --bt: #6ee7b7;
    --ws: #78350f; --wt: #fcd34d;
    --ds: #7f1d1d; --dt: #fca5a5;
}
```

---

## 3. Komponen Antarmuka Inti

### 3.1 Container Kartu (`.card` dan `.card-form`)
- **`.card`**:
  Wadah modular untuk tabel data, ringkasan analitik, dan panel informasi. Mengisi 100% lebar kolom induk dengan padding `var(--sp-lg)`, border `1px solid var(--border)`, dan latar `var(--neutral)`.
- **`.card-form`**:
  Pembatas lebar form input data:
  ```css
  .card-form { max-width: 640px; margin: 0 auto; }
  ```
  Menjaga konsentrasi fokus pengisian form dan mencegah jarak tempuh mata yang melelahkan pada monitor resolusi ultrawide.

### 3.2 Struktur Navigasi (Dual-Mode Sidebar)
- **Desktop (≥1024px)**:
  Sidebar kiri tetap (*fixed*) selebar 256px dengan latar `var(--neutral)`. Mengelompokkan modul ke dalam grup akordeon independen (`<details class="nav-group">`). Keadaan buka/tutup akordeon disimpan ke `localStorage` secara persisten pada event native `toggle`.
- **Mobile (<1024px)**:
  Sidebar tersembunyi digantikan oleh topbar ringkas 56px dengan tombol drawer hamburger. Drawer meluncur dari kiri dengan overlay redup (`backdrop z-index 45`, `drawer z-index 50`), didukung navigasi sentuh ramah jari.

### 3.3 Sistem Badge dan Penanda Zona
Lencana (*badge*) dirancang seragam menggunakan tata letak `inline-flex` presisi:
- `.badge`: padding `4px 10px`, font-size `.75rem`, font-weight `600`, border-radius `var(--r-full)`.
- `.badge-success`: Aksen hijau (Status Aktif, Selesai, Kredit Poin).
- `.badge-warning`: Aksen kuning emas (Status Penanganan, Tingkat Sedang).
- `.badge-danger`: Aksen merah tegas (Status Dibuang, Tingkat Berat, Zero Tolerance, Debit Poin).
- `.badge-info`: Aksen biru laut (Tingkat Ringan, Identitas Peran).
- `.badge-neutral`: Aksen abu-abu netral (Read-only, Label Kategori).

### 3.4 Modal Viewport Center
Komponen pop-up dan dialog konfirmasi (seperti modal konfirmasi hapus akun dan peringatan Force Dark) menerapkan teknik isolasi viewport:
```css
.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--sp-md);
}

@media (min-width: 1024px) {
    .modal-overlay {
        padding-left: calc(256px + var(--sp-md)); /* Koreksi offset lebar sidebar desktop */
    }
}
```
Aturan ini menjamin kotak modal berada tepat di tengah area kerja aktif staf, bukan tertutup atau terdorong oleh sidebar desktop.

---

## 4. Mekanisme Dark Mode & Penanganan Browser Force Dark

### 4.1 Transisi Tema Bebas Flash (Anti-FOUC)
Pencegahan kedipan putih saat navigasi mode gelap ditangani menggunakan script IIFE sinkron di `<head>` sebelum CSS pertama dirender:
```javascript
(function() {
    const s = localStorage.getItem('theme');
    if (s === 'dark' || (!s && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.add('light');
    }
})();
```

### 4.2 Deteksi Force Dark Mode Bawaan Peramban
Beberapa peramban berbasis Chromium memiliki fitur pemaksa tema gelap otomatis (*Auto Dark Mode* / *Force Dark Mode*) yang membalikkan warna latar secara agresif. Ketika pengguna sengaja beralih ke Mode Terang, peramban dapat merusak kontras.

Sistem mengatasi ini dengan **elemen probe off-screen**:
```html
<div id="fd-probe" style="position:fixed;left:-9999px;top:-9999px;width:10px;height:10px;background-color:#ffffff;color-scheme:only light" aria-hidden="true"></div>
```
- Saat pengguna menekan tombol tema ke *Light Mode*, fungsi `toggleTheme()` memeriksa komputasi RGB probe via `window.getComputedStyle(probe).backgroundColor`.
- Jika nilai channel RGB terdeteksi berada di bawah 180 (diinversi peramban), sistem menampilkan modal edukatif ramah pengguna yang memberitahukan bahwa fitur peramban aktif.

---

## 5. Tata Letak Form dan Responsivitas Input
1. **Aturan `box-sizing: border-box` Wajib**: Seluruh input teks, area teks, dan seleksi dropdown menerapkan perhitungan batas kotak penuh agar padding dan garis tepi tidak meluap keluar dari batas kartu.
2. **Tata Letak Rombel / Kolektif**: Tombol aksi massal (seperti pemilihan satu kelas dan tombol Kosongkan Semua) ditempatkan bersebelahan secara harmonis tanpa memakan ruang vertikal berlebih.
3. **Format Cetak Dokumen Resmi**: Halaman sertifikat dan surat penanganan kasus menerapkan stylesheet `@media print` terisolasi:
   - Menyembunyikan sidebar, topbar, dan tombol aksi (`.no-print`).
   - Margin kertas standar A4/F4 (1.5 cm) dengan logo dan kop institusi beresolusi tinggi.
