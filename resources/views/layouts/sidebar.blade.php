@php
    use App\Helpers\MenuHelper;
    $menuGroups = MenuHelper::getMenuGroups();
    $currentPath = request()->path();

    $initialSubmenus = [];
    foreach ($menuGroups as $groupIndex => $menuGroup) {
        foreach ($menuGroup['items'] as $itemIndex => $item) {
            if (isset($item['subItems'])) {
                foreach ($item['subItems'] as $subItem) {
                    $cleanPath = ltrim($subItem['path'], '/');
                    if ($currentPath === $cleanPath || request()->is($cleanPath . '*')) {
                        $initialSubmenus["{$groupIndex}-{$itemIndex}"] = true;
                    }
                }
            }
        }
    }
@endphp

<aside id="sidebar"
    class="fixed flex flex-col mt-0 top-0 start-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen w-[290px] transition-all duration-300 ease-in-out z-50 border-r border-gray-200"
    :class="{
        'w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen,
        'w-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen,
        'translate-x-0': $store.sidebar.isMobileOpen,
        'max-xl:-translate-x-full': !$store.sidebar.isMobileOpen
    }"
    x-data="{
        openSubmenus: (() => {
            const defaults = @js($initialSubmenus);
            try {
                const raw = localStorage.getItem('sidebar_open_submenus');
                if (raw !== null) {
                    return JSON.parse(raw);
                }
            } catch (e) {}
            return defaults;
        })(),
        init() {
            this.setupScrollPersistence();
        },
        toggleSubmenu(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            this.openSubmenus[key] = !this.openSubmenus[key];
            this.openSubmenus = Object.assign({}, this.openSubmenus);
            try {
                localStorage.setItem('sidebar_open_submenus', JSON.stringify(this.openSubmenus));
            } catch (e) {}
        },
        setupScrollPersistence() {
            const container = this.$refs.menuContainer;
            if (!container) return;
            const pos = sessionStorage.getItem('sidebar_scroll_top');
            if (pos !== null) {
                container.scrollTop = parseInt(pos, 10);
            }
            container.addEventListener('scroll', () => {
                sessionStorage.setItem('sidebar_scroll_top', container.scrollTop);
            }, { passive: true });
            window.addEventListener('beforeunload', () => {
                sessionStorage.setItem('sidebar_scroll_top', container.scrollTop);
            });
        },
        isSubmenuOpen(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            return !!this.openSubmenus[key];
        },
        isActive(path) {
            return window.location.pathname === path || '{{ $currentPath }}' === path.replace(/^\//, '');
        }
    }"
    @mouseenter="if (!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">

    <!-- Logo & Brand Toggle Section -->
    <div class="px-5 py-5 flex items-center border-b border-gray-100 dark:border-gray-800/80"
        :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'justify-center' : 'justify-between'">
        <a href="/" class="flex items-center gap-3 min-w-0">
            <img class="h-8 w-8 shrink-0 rounded-lg" src="/image.ico" alt="Logo" />
            <div x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="overflow-hidden">
                <span class="block font-bold text-gray-800 dark:text-white text-base leading-tight truncate">Pelanggaran Kuy</span>
            </div>
        </a>
        <button type="button" @click.stop="$store.sidebar.toggleExpanded()"
            x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
            class="hidden xl:inline-flex p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-white/5 transition"
            title="Kecilkan / Perbesar Sidebar">
            <svg class="size-5 shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="18" x="3" y="3" rx="2"/>
                <path d="M9 3v18"/>
                <path d="m14 9-3 3 3 3"/>
            </svg>
        </button>
    </div>

    <!-- Navigation Menu Content (Matches TailAdmin Exact Flow) -->
    <div x-ref="menuContainer" id="sidebarMenuContainer" class="flex-1 flex flex-col justify-between overflow-y-auto no-scrollbar px-5 py-6">
        <script>
            (function() {
                try {
                    var el = document.getElementById('sidebarMenuContainer');
                    var pos = sessionStorage.getItem('sidebar_scroll_top');
                    if (el && pos) el.scrollTop = parseInt(pos, 10);
                } catch (e) {}
            })();
        </script>
        <nav class="space-y-6">
            @foreach ($menuGroups as $groupIndex => $menuGroup)
                <div class="flex flex-col gap-2">
                    <!-- Menu Group Label -->
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1"
                        :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'text-center' : 'text-left'">
                        <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
                            {{ $menuGroup['title'] }}
                        </span>
                        <span x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen">&bull;&bull;&bull;</span>
                    </div>

                    <!-- Menu List -->
                    <ul class="flex flex-col gap-1">
                        @foreach ($menuGroup['items'] as $itemIndex => $item)
                            <li class="relative">
                                @if (isset($item['subItems']))
                                    <!-- Collapsible Menu Trigger -->
                                    <button type="button" @click="toggleSubmenu({{ $groupIndex }}, {{ $itemIndex }})"
                                        class="menu-item cursor-pointer group"
                                        :class="openSubmenus['{{ $groupIndex }}-{{ $itemIndex }}'] ? 'menu-item-active' : 'menu-item-inactive'">
                                        <span class="shrink-0 size-5 flex items-center justify-center"
                                            :class="openSubmenus['{{ $groupIndex }}-{{ $itemIndex }}'] ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                            {!! MenuHelper::getIconSvg($item['icon']) !!}
                                        </span>

                                        <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                            class="flex-1 text-left truncate">
                                            {{ $item['name'] }}
                                        </span>

                                        <!-- Chevron Down Icon with explicit path direction swap -->
                                        <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"
                                            x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                            class="ms-auto size-4.5 shrink-0"
                                            :class="openSubmenus['{{ $groupIndex }}-{{ $itemIndex }}'] ? 'text-brand-500' : 'text-gray-400'">
                                            <!-- Path saat terbuka (panah mengarah ke atas) -->
                                            <path x-show="openSubmenus['{{ $groupIndex }}-{{ $itemIndex }}']"
                                                d="M4.792 12.604L10 7.396l5.208 5.208" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <!-- Path saat tertutup (panah mengarah ke bawah) -->
                                            <path x-show="!openSubmenus['{{ $groupIndex }}-{{ $itemIndex }}']"
                                                d="M4.792 7.396L10 12.604l5.208-5.208" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>

                                    <!-- Collapsible Panel Content (TailAdmin Native Tree Hierarchy) -->
                                    <div x-show="openSubmenus['{{ $groupIndex }}-{{ $itemIndex }}'] && ($store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen)"
                                        class="overflow-hidden">
                                        <div class="my-2 ml-7 pl-3 border-l-2 border-slate-300 dark:border-slate-700">
                                            <ul class="flex flex-col gap-1">
                                                @foreach ($item['subItems'] as $subItem)
                                                    <li>
                                                        <a href="{{ $subItem['path'] }}"
                                                            class="group relative flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition-colors"
                                                            :class="isActive('{{ $subItem['path'] }}') ?
                                                                'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' :
                                                                'text-gray-900 hover:text-brand-600 hover:bg-gray-100 dark:text-gray-100 dark:hover:text-white dark:hover:bg-white/5'">
                                                            <span class="block truncate">{{ $subItem['name'] }}</span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @else
                                    <!-- Standalone Item -->
                                    <a href="{{ $item['path'] }}"
                                        class="menu-item group"
                                        :class="isActive('{{ $item['path'] }}') ? 'menu-item-active' : 'menu-item-inactive'">
                                        <span class="shrink-0 size-5 flex items-center justify-center"
                                            :class="isActive('{{ $item['path'] }}') ? 'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                            {!! MenuHelper::getIconSvg($item['icon']) !!}
                                        </span>

                                        <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                            class="flex-1 text-left truncate">
                                            {{ $item['name'] }}
                                        </span>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>
    </div>

    <!-- Bottom User Profile & Actions Footer -->
    <div class="p-4 border-t border-gray-100 dark:border-gray-800 flex flex-col gap-2">
        <div class="flex items-center gap-3 px-2 py-1.5"
            :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'justify-center' : ''">
            <div class="size-8 rounded-full bg-brand-500 text-white flex items-center justify-center text-xs font-bold shrink-0 overflow-hidden">
                @if(Auth::user()?->avatar_path)
                    <img src="{{ asset('storage/'.Auth::user()->avatar_path) }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                @endif
            </div>
            <div x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="flex-1 min-w-0">
                <div class="text-sm font-semibold text-gray-800 dark:text-white truncate">{{ Auth::user()->name ?? 'User' }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email ?? '' }}</div>
            </div>
        </div>

        <a href="{{ route('profile.edit') }}"
            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 transition"
            :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'justify-center' : ''"
            title="Pengaturan Akun">
            <svg class="size-5 shrink-0 text-gray-500 dark:text-gray-400" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="truncate">Pengaturan Akun</span>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit"
                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition cursor-pointer"
                :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'justify-center' : ''"
                title="Keluar">
                <svg class="size-5 shrink-0 text-red-600 dark:text-red-400" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" x2="9" y1="12" y2="12"/>
                </svg>
                <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="truncate">Keluar</span>
            </button>
        </form>
    </div>
</aside>
