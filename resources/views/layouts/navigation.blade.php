@php
    $groups = [
        [
            'label' => 'Data Referensi',
            'icon' => 'database',
            'items' => [
                ['route' => 'users.index', 'label' => 'Pengguna', 'pattern' => 'users.*', 'icon' => ''],
                ['route' => 'students.index', 'label' => 'Siswa', 'pattern' => 'students.*', 'icon' => ''],
                ['route' => 'classes.index', 'label' => 'Kelas', 'pattern' => 'classes.*', 'icon' => ''],
                ['route' => 'tahun-ajaran.index', 'label' => 'Tahun Ajaran', 'pattern' => 'tahun-ajaran.*', 'icon' => '' ],
            ],
        ],
        [
            'label' => 'Pelanggaran',
            'icon' => 'shield-alert',
            'items' => [
                ['route' => 'violation-categories.index', 'label' => 'Daftar Pelanggaran', 'pattern' => 'violation-categories.*', 'icon' => ''],
                ['route' => 'kasus-pelanggaran.index', 'label' => 'Kasus', 'pattern' => 'kasus-pelanggaran.*', 'icon' => ''],
            ],
        ],
        [
            'label' => 'Prestasi',
            'icon' => 'trophy',
            'items' => [
                ['route' => 'achievement-categories.index', 'label' => 'Kategori', 'pattern' => 'achievement-categories.*', 'icon' => ''],
                ['route' => 'achievement-records.index', 'label' => 'Rekam Prestasi', 'pattern' => 'achievement-records.*', 'icon' => ''],
            ],
        ],
        [
            'label' => 'Poin',
            'icon' => 'wallet',
            'items' => [
                ['route' => 'monitor-poin.index', 'label' => 'Monitor Poin', 'pattern' => 'monitor-poin.*', 'icon' => ''],
                ['route' => 'point-ledgers.index', 'label' => 'Riwayat Poin', 'pattern' => 'point-ledgers.*', 'icon' => ''],
            ],
        ],
        [
            'label' => 'Sistem',
            'icon' => 'settings',
            'items' => [
                ['route' => 'settings.index', 'label' => 'Pengaturan', 'pattern' => 'settings.*', 'icon' => ''],
            ],
        ],
    ];
    $isActive = fn($p) => request()->routeIs($p);
    $groupActive = fn($g) => collect($g['items'])->contains(fn($i) => $isActive($i['pattern']));
@endphp

{{-- Desktop sidebar (≥1024px) --}}
<aside class="sidebar" style="
    position:fixed;top:0;left:0;bottom:0;width:256px;
    background:var(--neutral);
    display:none;flex-direction:column;z-index:40;
    overflow-y:auto;
">
    {{-- Tanpa border-bottom & tanpa garis vertikal: strip atas menyatu dengan app-header
         (bg --neutral sama), garis vertikal pemisah baru digambar main (app.blade). --}}
    <div style="padding:var(--sp-lg)">
        <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:var(--sp-sm);text-decoration:none;color:var(--primary);font-size:1.125rem;font-weight:700">
            <img src="{{ asset('image.ico') }}" alt="Logo" style="width:24px;height:24px;border-radius:4px">
            <span>Pelanggaran Kuy</span>
        </a>
    </div>

    <nav style="flex:1;padding:var(--sp-sm)">
        {{-- Dashboard standalone --}}
        <a href="{{ route('dashboard') }}" style="
            display:flex;align-items:center;gap:var(--sp-sm);
            padding:10px var(--sp-md);border-radius:var(--r-sm);
            font-size:.875rem;font-weight:600;text-decoration:none;
            margin-bottom:var(--sp-sm);transition:background .15s,color .15s;
            {{ $isActive('dashboard') ? 'background:var(--surface);color:var(--on-surface);' : 'color:var(--on-surface-muted);' }}
        ">
            <i data-lucide="layout-dashboard" style="width:18px;height:18px;flex-shrink:0"></i>
            Dashboard
        </a>

        @foreach($groups as $group)
            @php $open = $groupActive($group); $slug = \Illuminate\Support\Str::slug($group['label']); @endphp
            <details class="nav-group" data-nav-key="{{ $slug }}" @if($open) open @endif style="margin-bottom:2px">
                <summary style="
                    display:flex;align-items:center;gap:var(--sp-sm);
                    padding:8px var(--sp-md);border-radius:var(--r-sm);
                    font-size:.75rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
                    cursor:pointer;list-style:none;user-select:none;
                    {{ $open ? 'color:var(--on-surface);' : 'color:var(--on-surface-muted);' }}
                ">
                    <i data-lucide="{{ $group['icon'] }}" style="width:16px;height:16px;flex-shrink:0"></i>
                    <span style="flex:1">{{ $group['label'] }}</span>
                    <i data-lucide="chevron-down" class="navchev" style="width:14px;height:14px;flex-shrink:0;transition:transform .15s"></i>
                </summary>
                <div style="padding:4px 0 6px 0">
                    @foreach($group['items'] as $item)
                        <a href="{{ route($item['route']) }}" style="
                            display:flex;align-items:center;gap:var(--sp-sm);
                            padding:9px var(--sp-md) 9px calc(var(--sp-md) + 20px);border-radius:var(--r-sm);
                            font-size:.875rem;font-weight:600;text-decoration:none;
                            margin-bottom:2px;transition:background .15s,color .15s;
                            {{ $isActive($item['pattern']) ? 'background:var(--surface);color:var(--on-surface);' : 'color:var(--on-surface-muted);' }}
                        ">
                            <i data-lucide="{{ $item['icon'] }}" style="width:18px;height:18px;flex-shrink:0"></i>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </details>
        @endforeach
    </nav>

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
            <div style="width:32px;height:32px;border-radius:var(--r-full);background:var(--primary);color:var(--on-primary);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0;overflow:hidden">
                @if(Auth::user()?->avatar_path)
                    <img src="{{ asset('storage/'.Auth::user()->avatar_path) }}" alt="{{ Auth::user()->name }}" style="width:100%;height:100%;object-fit:cover">
                @else
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                @endif
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
<div class="sidebar-overlay" x-data="{ open: JSON.parse(localStorage.getItem('sidebar-open') || 'false') }" @keydown.escape.window="open = false">
    <div class="mobile-topbar" style="
        display:none;position:sticky;top:0;z-index:50;
        background:var(--glass-bg);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
        border-bottom:1px solid var(--glass-border);box-shadow:var(--sh-g);
        height:56px;padding:0 var(--sp-md);
        align-items:center;justify-content:space-between;
    ">
        <div style="display:flex;align-items:center;gap:var(--sp-sm)">
            <button @click="open = !open; localStorage.setItem('sidebar-open', open)" style="width:40px;height:40px;border:none;background:transparent;color:var(--on-surface);cursor:pointer;display:flex;align-items:center;justify-content:center">
                <i data-lucide="menu" style="width:22px;height:22px"></i>
            </button>
        </div>
        <div style="display:flex;align-items:center;gap:var(--sp-sm)">
            <button onclick="toggleTheme()" style="width:36px;height:36px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--neutral);color:var(--on-surface);cursor:pointer;display:flex;align-items:center;justify-content:center">
                <i data-lucide="sun" class="icon-theme" style="width:16px;height:16px"></i>
            </button>
            <div style="width:32px;height:32px;border-radius:var(--r-full);background:var(--primary);color:var(--on-primary);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;overflow:hidden">
                @if(Auth::user()?->avatar_path)
                    <img src="{{ asset('storage/'.Auth::user()->avatar_path) }}" alt="{{ Auth::user()->name }}" style="width:100%;height:100%;object-fit:cover">
                @else
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                @endif
            </div>
        </div>
    </div>

    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="open = false; localStorage.setItem('sidebar-open', 'false')"
         style="position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:45">
    </div>

    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
         class="sidebar-mobile"
                style="position:fixed;top:0;left:0;bottom:0;width:280px;
                background:var(--neutral);z-index:50;
                display:flex;flex-direction:column;overflow-y:auto;
                box-shadow:var(--sh-md);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:var(--sp-md);border-bottom:1px solid var(--border)">
            <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:var(--sp-sm);text-decoration:none;color:var(--primary);font-size:1rem;font-weight:700">
                <img src="{{ asset('image.ico') }}" alt="Logo" style="width:20px;height:20px;border-radius:4px"> Pelanggaran Kuy
            </a>
            <button @click="open = false; localStorage.setItem('sidebar-open', 'false')" style="width:32px;height:32px;border:none;background:transparent;color:var(--on-surface-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;border-radius:var(--r-sm)">
                <i data-lucide="x" style="width:18px;height:18px"></i>
            </button>
        </div>
        <nav style="flex:1;padding:var(--sp-sm)">
            <a href="{{ route('dashboard') }}" @click="open = false; localStorage.setItem('sidebar-open', 'false')" style="
                display:flex;align-items:center;gap:var(--sp-sm);
                padding:10px var(--sp-md);border-radius:var(--r-sm);
                font-size:.875rem;font-weight:600;text-decoration:none;
                margin-bottom:var(--sp-sm);transition:background .15s;
                {{ $isActive('dashboard') ? 'background:var(--surface);color:var(--on-surface);' : 'color:var(--on-surface-muted);' }}
            ">
                <i data-lucide="layout-dashboard" style="width:18px;height:18px;flex-shrink:0"></i>
                Dashboard
            </a>
            @foreach($groups as $group)
                @php $open = $groupActive($group); $slug = \Illuminate\Support\Str::slug($group['label']); @endphp
                <details class="nav-group" data-nav-key="{{ $slug }}" @if($open) open @endif style="margin-bottom:2px">
                    <summary style="
                        display:flex;align-items:center;gap:var(--sp-sm);
                        padding:8px var(--sp-md);border-radius:var(--r-sm);
                        font-size:.75rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
                        cursor:pointer;list-style:none;
                        {{ $open ? 'color:var(--on-surface);' : 'color:var(--on-surface-muted);' }}
                    ">
                        <i data-lucide="{{ $group['icon'] }}" style="width:16px;height:16px;flex-shrink:0"></i>
                        <span style="flex:1">{{ $group['label'] }}</span>
                        <i data-lucide="chevron-down" class="navchev" style="width:14px;height:14px;flex-shrink:0;transition:transform .15s"></i>
                    </summary>
                    <div style="padding:4px 0 6px 0">
                        @foreach($group['items'] as $item)
                            <a href="{{ route($item['route']) }}" @click="open = false; localStorage.setItem('sidebar-open', 'false')" style="
                                display:flex;align-items:center;gap:var(--sp-sm);
                                padding:9px var(--sp-md) 9px calc(var(--sp-md) + 20px);border-radius:var(--r-sm);
                                font-size:.875rem;font-weight:600;text-decoration:none;
                                margin-bottom:2px;transition:background .15s;
                                {{ $isActive($item['pattern']) ? 'background:var(--surface);color:var(--on-surface);' : 'color:var(--on-surface-muted);' }}
                            ">
                                <i data-lucide="{{ $item['icon'] }}" style="width:18px;height:18px;flex-shrink:0"></i>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </nav>
        <div style="padding:var(--sp-md);border-top:1px solid var(--border)">
            <a href="{{ route('profile.edit') }}" @click="open = false; localStorage.setItem('sidebar-open', 'false')" style="display:flex;align-items:center;gap:var(--sp-sm);padding:8px var(--sp-md);font-size:.875rem;color:var(--on-surface-muted);text-decoration:none;border-radius:var(--r-sm)">
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

<script>
(function() {
    // --- Sidebar group collapse state → localStorage ---
    var NAV_KEYS = ['data-referensi','pelanggaran','prestasi','poin','sistem'];
    // Re-read setiap toggle — closure snapshot lama bikin state group lain ke-reset.
    function readNav() {
        try { return JSON.parse(localStorage.getItem('nav-open') || '{}'); }
        catch (e) { return {}; }
    }
    function writeNav(saved) {
        localStorage.setItem('nav-open', JSON.stringify(saved));
    }
    function loadNavState() {
        var raw = localStorage.getItem('nav-open');
        var persisted = raw !== null;
        var saved = persisted ? readNav() : {};
        NAV_KEYS.forEach(function(key) {
            var el = document.querySelector('[data-nav-key="' + key + '"]');
            if (!el) return;
            if (persisted) {
                if (saved[key] === false) el.removeAttribute('open');
                else if (saved[key] === true) el.setAttribute('open', '');
            } else {
                saved[key] = el.hasAttribute('open');
            }
        });
        if (!persisted) writeNav(saved);
    }
    document.querySelectorAll('.nav-group').forEach(function(group) {
        var key = group.getAttribute('data-nav-key');
        if (!key) return;
        group.addEventListener('toggle', function() {
            var saved = readNav();          // selalu baca terbaru
            saved[key] = group.hasAttribute('open');  // hanya key sendiri
            writeNav(saved);
        });
    });
    loadNavState();
})();

(function() {
    // Lock scroll sidebar + window saat pindah halaman / refresh.
    // sessionStorage per-tab, hilang saat tab ditutup.
    var S_KEY = 'sidebar-scroll';
    var W_KEY = 'window-scroll';

    function restoreSidebar() {
        var nodes = document.querySelectorAll('.sidebar, .sidebar-mobile');
        var saved = sessionStorage.getItem(S_KEY);
        if (saved === null) return;
        var y = parseInt(saved, 10) || 0;
        nodes.forEach(function(el) { el.scrollTop = y; });
    }
    function bindSidebar() {
        var nodes = document.querySelectorAll('.sidebar, .sidebar-mobile');
        nodes.forEach(function(el) {
            var ticking = false;
            el.addEventListener('scroll', function() {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(function() {
                    sessionStorage.setItem(S_KEY, String(el.scrollTop));
                    // sync ke semua sidebar node biar konsisten
                    var v = el.scrollTop;
                    nodes.forEach(function(n){ if(n!==el) n.scrollTop=v; });
                    ticking = false;
                });
            }, { passive: true });
        });
    }
    // restore setelah paint (2x rAF) biar height sudah final
    requestAnimationFrame(function(){ requestAnimationFrame(restoreSidebar); });
    bindSidebar();

    // --- window scroll lock (main content) ---
    var wSaved = sessionStorage.getItem(W_KEY);
    if (wSaved !== null) {
        var wy = parseInt(wSaved, 10) || 0;
        requestAnimationFrame(function(){ requestAnimationFrame(function(){ window.scrollTo(0, wy); }); });
    }
    var wTicking = false;
    window.addEventListener('scroll', function() {
        if (wTicking) return;
        wTicking = true;
        requestAnimationFrame(function(){
            sessionStorage.setItem(W_KEY, String(window.scrollY));
            wTicking = false;
        });
    }, { passive: true });
    // sebelum pindah halaman, simpan dulu (jaga kalau rAF kelewat)
    window.addEventListener('beforeunload', function(){
        sessionStorage.setItem(S_KEY, String((document.querySelector('.sidebar')||{}).scrollTop||0));
        sessionStorage.setItem(W_KEY, String(window.scrollY));
    });
})();
</script>

<style>
    .nav-group summary::-webkit-details-marker{display:none}
    .nav-group summary::marker{display:none}
    .nav-group[open] summary .navchev{transform:rotate(180deg)}
    /* Sembunyikan scrollbar visual sidebar — scroll tetap jalan via wheel/touch */
    .sidebar{scrollbar-width:none;-ms-overflow-style:none}
    .sidebar::-webkit-scrollbar{display:none;width:0;height:0}
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
