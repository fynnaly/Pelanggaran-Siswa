{{-- Desktop & Mobile Navigation --}}
<nav x-data="{ open: false }" style="background:var(--glass-bg);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-bottom:1px solid var(--glass-border);box-shadow:var(--sh-g);position:sticky;top:0;z-index:50;">
    <div style="max-width:1280px;margin:0 auto;padding:0 var(--sp-md);display:flex;align-items:center;justify-content:space-between;height:64px;gap:var(--sp-md);">
        {{-- Logo --}}
        <a href="{{ route('dashboard') }}" style="font-size:1.125rem;font-weight:700;color:var(--primary);white-space:nowrap;text-decoration:none">
            SMK Pelanggaran
        </a>

        {{-- Desktop Nav --}}
        <div style="display:none;gap:var(--sp-lg)" class="nav-links">
            @php
                $links = [
                    ['route' => 'dashboard', 'label' => 'Dashboard', 'pattern' => 'dashboard'],
                    ['route' => 'students.index', 'label' => 'Siswa', 'pattern' => 'students.*'],
                    ['route' => 'classes.index', 'label' => 'Kelas', 'pattern' => 'classes.*'],
                    ['route' => 'violation-categories.index', 'label' => 'Kategori', 'pattern' => 'violation-categories.*'],
                    ['route' => 'discipline-cases.index', 'label' => 'Kasus', 'pattern' => 'discipline-cases.*'],
                    ['route' => 'academic-years.index', 'label' => 'Tahun Ajaran', 'pattern' => 'academic-years.*'],
                ];
            @endphp
            @foreach($links as $link)
                <a href="{{ route($link['route']) }}"
                   style="text-decoration:none;font-size:.875rem;font-weight:600;transition:color .15s;{{ request()->routeIs($link['pattern']) ? 'color:var(--on-surface)' : 'color:var(--on-surface-muted)' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>

        {{-- Header Actions --}}
        <div style="display:flex;align-items:center;gap:var(--sp-sm)">
            <button onclick="toggleTheme()" style="width:40px;height:40px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--neutral);color:var(--on-surface);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.125rem;transition:background .15s" title="Toggle tema">
                <span x-data="{ dark: localStorage.getItem('theme') === 'dark' }" x-text="dark ? '&#9790;' : '&#9788;'">&#9788;</span>
            </button>
            <div style="width:36px;height:36px;border-radius:var(--r-full);background:var(--primary);color:var(--on-primary);display:flex;align-items:center;justify-content:center;font-size:.875rem;font-weight:700">
                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
            </div>

            {{-- Hamburger Mobile --}}
            <button @click="open = !open" style="display:none;width:40px;height:40px;border:none;background:transparent;color:var(--on-surface);cursor:pointer;font-size:1.25rem" class="hamburger-btn">
                <span x-show="!open">&#9776;</span>
                <span x-show="open">&#10005;</span>
            </button>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div x-show="open" x-transition style="display:none;padding:var(--sp-md);border-top:1px solid var(--border)" class="mobile-menu">
        @foreach($links as $link)
            <a href="{{ route($link['route']) }}"
               style="display:block;padding:var(--sp-sm) var(--sp-md);font-size:.875rem;font-weight:600;border-radius:var(--r-sm);text-decoration:none;margin-bottom:var(--sp-xs);{{ request()->routeIs($link['pattern']) ? 'background:var(--surface);color:var(--on-surface)' : 'color:var(--on-surface-muted)' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
        <div style="border-top:1px solid var(--border);margin-top:var(--sp-sm);padding-top:var(--sp-sm)">
            <a href="{{ route('profile.edit') }}" style="display:block;padding:var(--sp-sm) var(--sp-md);font-size:.875rem;color:var(--on-surface-muted);text-decoration:none">Profile</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="display:block;width:100%;text-align:left;padding:var(--sp-sm) var(--sp-md);font-size:.875rem;color:var(--danger);background:none;border:none;cursor:pointer;font-family:inherit">Logout</button>
            </form>
        </div>
    </div>
</nav>

<style>
    @media(min-width:640px) {
        .nav-links { display:flex !important; }
        .hamburger-btn { display:none !important; }
    }
    @media(max-width:639px) {
        .hamburger-btn { display:flex !important; }
        .mobile-menu { display:block !important; }
    }
</style>
