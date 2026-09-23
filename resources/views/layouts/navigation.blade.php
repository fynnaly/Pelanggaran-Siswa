@php
    $links = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'pattern' => 'dashboard', 'icon' => 'layout-dashboard'],
        ['route' => 'students.index', 'label' => 'Siswa', 'pattern' => 'students.*', 'icon' => 'users'],
        ['route' => 'classes.index', 'label' => 'Kelas', 'pattern' => 'classes.*', 'icon' => 'school'],
        ['route' => 'violation-categories.index', 'label' => 'Pelanggaran', 'pattern' => 'violation-categories.*', 'icon' => 'tags'],
        ['route' => 'kasus-pelanggaran.index', 'label' => 'Kasus', 'pattern' => 'kasus-pelanggaran.*', 'icon' => 'file-text'],
        ['route' => 'tahun-ajaran.index', 'label' => 'Tahun Ajaran', 'pattern' => 'tahun-ajaran.*', 'icon' => 'calendar'],
        ['route' => 'point-ledgers.index', 'label' => 'Poin', 'pattern' => 'point-ledgers.*', 'icon' => 'wallet'],
        ['route' => 'achievement-categories.index', 'label' => 'Kategori Prestasi', 'pattern' => 'achievement-categories.*', 'icon' => 'trophy'],
        ['route' => 'achievement-records.index', 'label' => 'Rekam Prestasi', 'pattern' => 'achievement-records.*', 'icon' => 'award'],
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
            <img src="{{ asset('image.ico') }}" alt="Logo" style="width:24px;height:24px;border-radius:4px">
            <span>Pelanggaran Kuy</span>
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
<div class="sidebar-overlay" x-data="{ open: JSON.parse(localStorage.getItem('sidebar-open') || 'false') }" @keydown.escape.window="open = false">
    {{-- Mobile top bar --}}
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
            <div style="width:32px;height:32px;border-radius:var(--r-full);background:var(--primary);color:var(--on-primary);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700">
                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
            </div>
        </div>
    </div>

    {{-- Backdrop --}}
    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="open = false; localStorage.setItem('sidebar-open', 'false')"
         style="position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:45">
    </div>

    {{-- Drawer --}}
    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
         style="position:fixed;top:0;left:0;bottom:0;width:280px;
                background:var(--neutral);z-index:50;
                display:flex;flex-direction:column;overflow-y:auto;
                box-shadow:var(--sh-md);">
        {{-- Drawer header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:var(--sp-md);border-bottom:1px solid var(--border)">
            <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:var(--sp-sm);text-decoration:none;color:var(--primary);font-size:1rem;font-weight:700">
                <img src="{{ asset('image.ico') }}" alt="Logo" style="width:20px;height:20px;border-radius:4px"> Pelanggaran Kuy
            </a>
            <button @click="open = false; localStorage.setItem('sidebar-open', 'false')" style="width:32px;height:32px;border:none;background:transparent;color:var(--on-surface-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;border-radius:var(--r-sm)">
                <i data-lucide="x" style="width:18px;height:18px"></i>
            </button>
        </div>
        {{-- Drawer nav --}}
        <nav style="flex:1;padding:var(--sp-sm)">
            @foreach($links as $link)
                <a href="{{ route($link['route']) }}" @click="open = false; localStorage.setItem('sidebar-open', 'false')" style="
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
