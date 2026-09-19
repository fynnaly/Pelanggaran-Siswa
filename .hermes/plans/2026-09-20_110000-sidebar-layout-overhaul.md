# Plan: Sidebar Navigation + Responsive Layout Overhaul

## Goal
Ganti navigasi top-bar menjadi sidebar (PC) + hamburger drawer (HP), buat layout profesional standar dashboard modern.

## Current Context
- **Branch**: `feat/view-design-system` (up-to-date di remote)
- **Layout saat ini**: `app.blade.php` pakai top nav (`navigation.blade.php`) + sticky header slot + `<main>` full-width
- **Masalah**: Navigasi top-bar susah diklik (terutama di HP), page-header `flex justify-between` berantakan di mobile
- **Tech stack**: Laravel Blade + Alpine.js + Lucide CDN (inline CSS, NO Tailwind utility classes)
- **19 view files** pakai `<x-app-layout>` + `<x-slot name="header">`

## Architecture
Layout berubah dari:
```
[Top Nav - sticky]
  [Logo] [Nav links horizontal] [Theme] [Avatar]
[Header slot - sticky]
[Main content - full width]
```
Menjadi:
```
DESKTOP (≥1024px):
┌──────────┬──────────────────────────────┐
│ Sidebar  │ Top bar (breadcrumb/title)   │
│ (fixed)  │                              │
│          │ Main content                 │
│          │                              │
└──────────┴──────────────────────────────┘

MOBILE (<1024px):
[Top bar: hamburger + logo + theme + avatar]
[Main content full-width]
[Overlay sidebar → slide from left]
```

## Step-by-Step Tasks

### Task 1: Rewrite `resources/views/layouts/navigation.blade.php`
Sidebar component dengan Alpine.js.

**Desktop (≥1024px)**: Fixed left sidebar 256px, full height, `var(--neutral)` bg, border-right.
**Mobile (<1024px)**: Hidden by default, slide-in overlay from left saat hamburger diklik.

Isi sidebar:
- **Top**: Logo + nama sekolah (shield icon)
- **Middle**: Nav links vertikal — Dashboard, Siswa, Kelas, Kategori, Kasus, Tahun Ajaran. Setiap link punya icon Lucide + label. Active state: background highlight + bold
- **Bottom**: Theme toggle (sun/moon) + User avatar + nama + Profile link + Logout button

```html
{{-- resources/views/layouts/navigation.blade.php --}}
@php
    $links = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'pattern' => 'dashboard', 'icon' => 'layout-dashboard'],
        ['route' => 'students.index', 'label' => 'Siswa', 'pattern' => 'students.*', 'icon' => 'users'],
        ['route' => 'classes.index', 'label' => 'Kelas', 'pattern' => 'classes.*', 'icon' => 'school'],
        ['route' => 'violation-categories.index', 'label' => 'Kategori', 'pattern' => 'violation-categories.*', 'icon' => 'tags'],
        ['route' => 'discipline-cases.index', 'label' => 'Kasus', 'pattern' => 'discipline-cases.*', 'icon' => 'file-text'],
        ['route' => 'academic-years.index', 'label' => 'Tahun Ajaran', 'pattern' => 'academic-years.*', 'icon' => 'calendar'],
    ];
@endphp

{{-- Desktop sidebar (≥1024px) --}}
<aside class="sidebar" style="
    position:fixed;top:0;left:0;bottom:0;width:256px;
    background:var(--neutral);border-right:1px solid var(--border);
    display:none;flex-direction:column;z-index:40;
    overflow-y:auto;
">
    {{-- Logo --}}
    <div style="padding:var(--sp-lg);border-bottom:1px solid var(--border)">
        <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:var(--sp-sm);text-decoration:none;color:var(--primary);font-size:1.125rem;font-weight:700">
            <i data-lucide="shield" style="width:24px;height:24px"></i>
            <span>SMK Pelanggaran</span>
        </a>
    </div>

    {{-- Nav links --}}
    <nav style="flex:1;padding:var(--sp-md) var(--sp-sm)">
        @foreach($links as $link)
            <a href="{{ route($link['route']) }}" style="
                display:flex;align-items:center;gap:var(--sp-sm);
                padding:10px var(--sp-md);border-radius:var(--r-sm);
                font-size:.875rem;font-weight:600;text-decoration:none;
                margin-bottom:2px;transition:background .15s,color .15s;
                {{ request()->routeIs($link['pattern'])
                    ? 'background:var(--surface);color:var(--on-surface);'
                    : 'color:var(--on-surface-muted);' }}
            ">
                <i data-lucide="{{ $link['icon'] }}" style="width:18px;height:18px;flex-shrink:0"></i>
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>

    {{-- Bottom: user + theme --}}
    <div style="padding:var(--sp-md);border-top:1px solid var(--border)">
        <button onclick="toggleTheme()" style="
            display:flex;align-items:center;gap:var(--sp-sm);width:100%;
            padding:8px var(--sp-md);border-radius:var(--r-sm);border:none;
            background:transparent;color:var(--on-surface-muted);
            font-size:.875rem;font-weight:600;cursor:pointer;font-family:inherit;
            transition:background .15s;
        " onmouseover="this.style.background='var(--surface)'" onmouseout="this.style.background='transparent'">
            <i data-lucide="sun" class="icon-theme" style="width:18px;height:18px"></i>
            <span class="theme-label">Mode Terang</span>
        </button>
        <div style="display:flex;align-items:center;gap:var(--sp-sm);padding:8px var(--sp-md);margin-top:var(--sp-xs)">
            <div style="width:32px;height:32px;border-radius:var(--r-full);background:var(--primary);color:var(--on-primary);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0">
                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-size:.875rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ Auth::user()->name ?? 'User' }}</div>
                <div style="font-size:.75rem;color:var(--on-surface-muted)">{{ Auth::user()->email ?? '' }}</div>
            </div>
        </div>
        <a href="{{ route('profile.edit') }}" style="display:flex;align-items:center;gap:var(--sp-sm);padding:8px var(--sp-md);font-size:.875rem;color:var(--on-surface-muted);text-decoration:none;border-radius:var(--r-sm);margin-top:var(--sp-xs)">
            <i data-lucide="settings" style="width:16px;height:16px"></i> Pengaturan
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" style="display:flex;align-items:center;gap:var(--sp-sm);width:100%;padding:8px var(--sp-md);font-size:.875rem;color:var(--danger);background:none;border:none;cursor:pointer;font-family:inherit;border-radius:var(--r-sm);margin-top:2px">
                <i data-lucide="log-out" style="width:16px;height:16px"></i> Keluar
            </button>
        </form>
    </div>
</aside>

{{-- Mobile overlay + hamburger --}}
<div class="sidebar-overlay" x-data="{ open: false }" @keydown.escape.window="open = false">
    {{-- Mobile top bar --}}
    <div class="mobile-topbar" style="
        display:none;position:sticky;top:0;z-index:50;
        background:var(--glass-bg);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
        border-bottom:1px solid var(--glass-border);box-shadow:var(--sh-g);
        height:56px;padding:0 var(--sp-md);
        align-items:center;justify-content:space-between;
    ">
        <div style="display:flex;align-items:center;gap:var(--sp-sm)">
            <button @click="open = !open" style="width:40px;height:40px;border:none;background:transparent;color:var(--on-surface);cursor:pointer;display:flex;align-items:center;justify-content:center">
                <i data-lucide="menu" style="width:22px;height:22px"></i>
            </button>
            <a href="{{ route('dashboard') }}" style="font-size:1rem;font-weight:700;color:var(--primary);text-decoration:none;display:flex;align-items:center;gap:6px">
                <i data-lucide="shield" style="width:20px;height:20px"></i> SMK
            </a>
        </div>
        <div style="display:flex;align-items:center;gap:var(--sp-sm)">
            <button onclick="toggleTheme()" style="width:36px;height:36px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--neutral);color:var(--on-surface);cursor:pointer;display:flex;align-items:center;justify-content:center">
                <i data-lucide="sun" class="icon-theme" style="width:16px;height:16px"></i>
            </button>
            <div style="width:32px;height:32px;border-radius:var(--r-full);background:var(--primary);color:var(--on-primary);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700">
                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
            </div>
        </div>
    </div>

    {{-- Backdrop --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="open = false"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:45">
    </div>

    {{-- Drawer --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
         style="display:none;position:fixed;top:0;left:0;bottom:0;width:280px;
                background:var(--neutral);z-index:50;
                display:flex;flex-direction:column;overflow-y:auto;
                box-shadow:var(--sh-md);">
        {{-- Drawer header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:var(--sp-md);border-bottom:1px solid var(--border)">
            <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:var(--sp-sm);text-decoration:none;color:var(--primary);font-size:1rem;font-weight:700">
                <i data-lucide="shield" style="width:20px;height:20px"></i> SMK Pelanggaran
            </a>
            <button @click="open = false" style="width:32px;height:32px;border:none;background:transparent;color:var(--on-surface-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;border-radius:var(--r-sm)">
                <i data-lucide="x" style="width:18px;height:18px"></i>
            </button>
        </div>
        {{-- Drawer nav --}}
        <nav style="flex:1;padding:var(--sp-sm)">
            @foreach($links as $link)
                <a href="{{ route($link['route']) }}" @click="open = false" style="
                    display:flex;align-items:center;gap:var(--sp-sm);
                    padding:10px var(--sp-md);border-radius:var(--r-sm);
                    font-size:.875rem;font-weight:600;text-decoration:none;
                    margin-bottom:2px;transition:background .15s;
                    {{ request()->routeIs($link['pattern'])
                        ? 'background:var(--surface);color:var(--on-surface);'
                        : 'color:var(--on-surface-muted);' }}
                ">
                    <i data-lucide="{{ $link['icon'] }}" style="width:18px;height:18px;flex-shrink:0"></i>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>
        {{-- Drawer bottom --}}
        <div style="padding:var(--sp-md);border-top:1px solid var(--border)">
            <a href="{{ route('profile.edit') }}" @click="open = false" style="display:flex;align-items:center;gap:var(--sp-sm);padding:8px var(--sp-md);font-size:.875rem;color:var(--on-surface-muted);text-decoration:none;border-radius:var(--r-sm)">
                <i data-lucide="settings" style="width:16px;height:16px"></i> Pengaturan
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="display:flex;align-items:center;gap:var(--sp-sm);width:100%;padding:8px var(--sp-md);font-size:.875rem;color:var(--danger);background:none;border:none;cursor:pointer;font-family:inherit;border-radius:var(--r-sm)">
                    <i data-lucide="log-out" style="width:16px;height:16px"></i> Keluar
                </button>
            </form>
        </div>
    </div>
</div>

<script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>

<style>
    @media(min-width:1024px) {
        .sidebar { display:flex !important; }
        .sidebar-overlay, .mobile-topbar { display:none !important; }
        .sidebar-overlay .mobile-topbar { display:none !important; }
    }
    @media(max-width:1023px) {
        .sidebar { display:none !important; }
        .sidebar-overlay > .mobile-topbar { display:flex !important; }
    }
</style>
```

### Task 2: Rewrite `resources/views/layouts/app.blade.php` layout structure

Ubah dari:
```
@include('layouts.navigation')   ← top nav
<header>{{ $header }}</header>   ← sticky page-header
<main>{{ $slot }}</main>
```
Menjadi:
```
@include('layouts.navigation')   ← contains sidebar + mobile-topbar
<div class="app-shell">          ← new wrapper
    <header>{{ $header }}</header>  ← in-content top bar (breadcrumb + title)
    <main>{{ $slot }}</main>
</div>
```

CSS baru di `<style>`:
```css
/* ── Sidebar Layout Shell ── */
/* Desktop: main content shifted right by sidebar width */
@media(min-width:1024px) {
    .app-shell { margin-left: 256px; }
}

/* Header in-content (not sticky — sidebar handles nav) */
.app-header {
    background: var(--neutral);
    border-bottom: 1px solid var(--border);
    padding: var(--sp-md) var(--sp-lg);
}
```

Hapus CSS `.page-header` lama, ganti:
```css
.page-header {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: var(--sp-md); width: 100%;
}
.page-header h1 { margin-bottom: var(--sp-xs); }
.page-header > div:first-child { flex: 1; min-width: 200px; }
@media(max-width:639px) {
    .page-header { flex-direction: column; align-items: stretch; }
    .page-header .btn { width: 100%; justify-content: center; }
    .page-header > div:last-child { width: 100%; display: flex; flex-wrap: wrap; gap: var(--sp-sm); }
    .page-header > div:last-child .btn { flex: 1; min-width: 120px; }
}
```

### Task 3: Update all 19 view files — header slot

Semua view pakai `<x-slot name="header">` yang render di dalam `.app-header`. Yang perlu diubah di setiap view:
- **BISA DIHAPUS**: `<div class="breadcrumb">...</div>` di dalam page-header — pindahkan ke `$header` slot secara terpisah
- **Layout `$header` slot** jadi: breadcrumb di atas, title + actions di bawah

**Contoh pattern untuk semua views:**
```blade
<x-slot name="header">
    <div class="breadcrumb">Beranda / [Section]</div>
    <div class="page-header">
        <div>
            <h1>[Page Title]</h1>
            <p class="text-muted text-sm">[Subtitle]</p>
        </div>
        <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
            {{-- action buttons --}}
        </div>
    </div>
</x-slot>
```

### Files to update (19 views):

| # | File | Page Title |
|---|------|-----------|
| 1 | `resources/views/dashboard.blade.php` | Dashboard |
| 2 | `resources/views/students/index.blade.php` | Daftar Siswa |
| 3 | `resources/views/students/create.blade.php` | Tambah Siswa |
| 4 | `resources/views/students/edit.blade.php` | Edit: {nama} |
| 5 | `resources/views/classes/index.blade.php` | Daftar Kelas |
| 6 | `resources/views/classes/create.blade.php` | Tambah Kelas |
| 7 | `resources/views/classes/edit.blade.php` | Edit: {nama} |
| 8 | `resources/views/violation-categories/index.blade.php` | Kategori Pelanggaran |
| 9 | `resources/views/violation-categories/create.blade.php` | Tambah Kategori |
| 10 | `resources/views/violation-categories/edit.blade.php` | Edit: {kode} |
| 11 | `resources/views/discipline-cases/index.blade.php` | Kasus Pelanggaran |
| 12 | `resources/views/discipline-cases/create.blade.php` | Buat Kasus Baru |
| 13 | `resources/views/discipline-cases/show.blade.php` | Detail Kasus |
| 14 | `resources/views/academic-years/index.blade.php` | Tahun Ajaran |
| 15 | `resources/views/academic-years/create.blade.php` | Tambah Tahun Ajaran |
| 16 | `resources/views/academic-years/edit.blade.php` | Edit Tahun Ajaran |
| 17 | `resources/views/point-ledgers/show.blade.php` | Ledger Poin |
| 18 | `resources/views/profile/edit.blade.php` | Profil |
| 19 | `resources/views/point-ledgers/index.blade.php` | Buku Poin |

### Task 4: Update dark mode CSS token untuk sidebar

`.dark` tokens sudah ada — pastikan sidebar pakai `var(--neutral)` bg (bukan `var(--surface)`), sehingga dark mode otomatis gelap.

### Task 5: Commit + push

```bash
git add -A
git commit -m "feat: sidebar navigation (desktop) + drawer (mobile) + layout overhaul"
git push origin feat/view-design-system
```

## Risks & Tradeoffs
- **Sidebar offset**: Desktop content shifted 256px right. Jika ada view yang pakai `max-width:1280px` di `.main-wrap`, akan terasa sempit — resolved dengan `margin-left:256px` + content max-width tetap 1024px (total visible = 256 + 1024 = 1280px)
- **Mobile drawer z-index**: Overlay harus di atas semua konten. Pakai z-index 50 untuk drawer, 45 untuk backdrop
- **Lucide re-init**: `lucide.createIcons()` harus dipanggil setelah Alpine render drawer. Pindahkan ke `DOMContentLoaded` di navigation.blade.php
- **Auth components** (`profile/edit`, `auth/*`) — profile edit perlu diupdate, auth pages (login, register) pakai `layouts/guest.blade.php` — TIDAK DIUBAH

## Verification
1. `npm run build` — pastikan tidak ada error
2. Buka `http://localhost:8000/dashboard` di desktop — sidebar visible, content shifted right
3. Resize browser ke <1024px — sidebar hilang, muncul hamburger + topbar
4. Klik hamburger — drawer slide dari kiri, backdrop overlay
5. Toggle dark mode di sidebar — semua berubah
6. Buka `/students`, `/classes`, `/discipline-cases` — navigasi aktif highlight
7. Buka di HP (atau devtools responsive) — full mobile experience
