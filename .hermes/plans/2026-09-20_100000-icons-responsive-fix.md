# Plan: Icons + Responsive Fix

## Masalah
1. **Tidak ada icon** - Semua tombol/aksi pakai teks saja ("E", "X", "V"), tidak profesional
2. **Dark mode toggle** - Tombol cuma "O", butuh icon sun/moon
3. **Responsive broken** - Page header `flex justify-between` berantakan di mobile (contoh: "5 siswa terdaftar" di students/index)
4. **Berlaku untuk SEMUA halaman**

## Solusi

### 1. Icon Library: Lucide Icons (CDN)
- Ukuran kecil (~3KB per icon, tree-shakeable via CDN)
- Konsisten, ringan, modern
- Bukan emoji (text-only labels tetap)
- CDN: `https://unpkg.com/lucide@latest`

### 2. Icon yang ditambahkan
| Lokasi | Icon | Kegunaan |
|--------|------|----------|
| Navigation | LayoutDashboard, Users, BookOpen, Shield, FileText, Calendar | Menu items |
| Dark mode toggle | Sun / Moon | Toggle tema |
| Page header buttons | Plus, Download, Upload, Search, Filter | Aksi utama |
| Table action buttons | Eye, Pencil, Trash2 | Lihat/Edit/Hapus |
| Dashboard stat cards | Users, AlertTriangle, CheckCircle, BookOpen | Visual stat |
| Dashboard quick actions | FilePlus, Search, Download | Aksi cepat |
| Breadcrumb | ChevronRight | Separator |
| Empty state | Inbox | Tidak ada data |
| Pagination info | - | Teks saja (tidak perlu icon) |

### 3. Responsive Fix
**Root cause:** `page-header` pakai `display:flex;justify-content:space-between` tanpa `flex-wrap` yang memadai, dan `gap` terlalu kecil di mobile.

**Fix pattern untuk semua halaman:**
```css
.page-header {
    display: flex;
    align-items: flex-start; /* bukan center - biar wrap natural */
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--sp-md); /* 16px, cukup buat mobile */
}
```

Tambahan di `app.blade.php`:
```css
@media(max-width:639px) {
    .page-header { flex-direction: column; }
    .page-header .btn { width: 100%; justify-content: center; }
}
```

### 4. File yang diubah
1. `layouts/app.blade.php` - Tambah CSS icon classes + responsive fix
2. `layouts/navigation.blade.php` - Icon di nav links + hamburger
3. `dashboard.blade.php` - Stat card icons + quick action icons
4. `students/index.blade.php` - Table action icons + header responsive
5. `discipline-cases/index.blade.php` - Table action icons + header responsive
6. `discipline-cases/show.blade.php` - Header icon
7. `academic-years/index.blade.php` - Table action icons
8. `academic-years/create.blade.php` - Header icon
9. `academic-years/edit.blade.php` - Header icon
10. `classes/index.blade.php` - Table action icons
11. `classes/create.blade.php` - Header icon
12. `classes/edit.blade.php` - Header icon
13. `violation-categories/index.blade.php` - Table action icons
14. `violation-categories/create.blade.php` - Header icon
15. `violation-categories/edit.blade.php` - Header icon
16. `students/create.blade.php` - Header icon
17. `students/edit.blade.php` - Header icon
18. `point-ledgers/show.blade.php` - Header icon

### 5. Icon Style di app.blade.php
```css
/* Icons - Lucide inline */
.icon { width: 18px; height: 18px; stroke: currentColor; stroke-width: 2; fill: none; stroke-linecap: round; stroke-linejoin: round; vertical-align: middle; }
.icon-sm { width: 14px; height: 14px; }
.icon-lg { width: 22px; height: 22px; }
```

## Urutan Eksekusi
1. Update `app.blade.php` - tambah icon CSS + responsive fix
2. Update `navigation.blade.php` - icon nav + sun/moon toggle
3. Update `dashboard.blade.php` - stat icons + quick action icons
4. Update semua index views (students, cases, academic-years, classes, kategori) - responsive header + action icons
5. Update semua create/edit views - responsive header
6. Update show views - header icons
7. Test di browser
8. Commit + push
