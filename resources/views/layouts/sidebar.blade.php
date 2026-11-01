@php
    use App\Helpers\MenuHelper;
    use Illuminate\Support\Str;
    $menuGroups = MenuHelper::getMenuGroups();
    $currentPath = request()->path();
@endphp

<aside id="sidebar"
    class="fixed flex flex-col mt-0 top-0 start-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-[width] duration-200 ease-in-out z-110 border-r border-gray-200"
    :class="{
        'w-[260px]': $store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen,
        'w-[64px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen,
        'shadow-2xl': !$store.sidebar.isExpanded && $store.sidebar.isHovered,
        'translate-x-0': $store.sidebar.isMobileOpen,
        'max-xl:-translate-x-full': !$store.sidebar.isMobileOpen
    }"
    @mouseenter="$store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)"
    x-data="{
        openSubmenus: (() => {
            try {
                const raw = localStorage.getItem('sidebar_open_submenus');
                if (raw) {
                    const parsed = JSON.parse(raw);
                    if (typeof parsed === 'object' && parsed !== null) {
                        return parsed;
                    }
                }
            } catch (e) {}
            return {};
        })(),
        init() {
            this.setupScrollPersistence();
        },
        isOpen() {
            return this.$store.sidebar.isExpanded || this.$store.sidebar.isHovered || this.$store.sidebar.isMobileOpen;
        },
        isSubmenuOpen(key) {
            return !!this.openSubmenus[key];
        },
        toggleSubmenu(key) {
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
        isActive(path) {
            return window.location.pathname === path || '{{ $currentPath }}' === path.replace(/^\//, '');
        },
        isGroupActive(subItems) {
            if (!subItems) return false;
            return subItems.some(sub => this.isActive(sub.path));
        }
    }">

    <!-- Logo & Brand Header Section -->
    <div class="h-16 flex items-center px-4 border-b border-gray-100 dark:border-gray-800 shrink-0 overflow-hidden">
        <a href="/" class="flex items-center gap-3 min-w-0">
            <img class="h-8 w-8 shrink-0 rounded-lg" src="/image.ico" alt="Logo" />
            <span class="font-bold text-gray-800 dark:text-white text-base leading-tight truncate transition-opacity duration-200"
                :class="isOpen() ? 'opacity-100' : 'opacity-0 w-0 hidden'">
                Pelanggaran Kuy
            </span>
        </a>
    </div>

    <!-- Navigation Menu Content Container -->
    <div x-ref="menuContainer" id="sidebarMenuContainer"
        class="flex-1 flex flex-col justify-between overflow-y-auto overflow-x-hidden no-scrollbar py-4 px-2.5">
        
        <nav class="space-y-6">
            @foreach ($menuGroups as $groupIndex => $menuGroup)
                <div class="flex flex-col gap-1.5">
                    <!-- Menu Group Label -->
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 px-3 mb-1"
                        :class="isOpen() ? 'block' : 'hidden'">
                        {{ $menuGroup['title'] }}
                    </div>
                    <div :class="!isOpen() ? 'block' : 'hidden'" class="w-6 h-px mx-auto bg-gray-200 dark:bg-gray-800 my-1.5"></div>

                    <!-- Menu List -->
                    <ul class="flex flex-col gap-1">
                        @foreach ($menuGroup['items'] as $itemIndex => $item)
                            @php
                                $menuKey = Str::slug($item['name']);
                            @endphp
                            <li class="relative">
                                @if (isset($item['subItems']))
                                    <!-- Accordion Submenu Trigger -->
                                    <button type="button" @click="toggleSubmenu('{{ $menuKey }}')"
                                        class="menu-item cursor-pointer group flex items-center justify-between w-full h-10 px-3 rounded-lg text-sm font-medium transition-colors"
                                        :class="isGroupActive(@js($item['subItems'])) || isSubmenuOpen('{{ $menuKey }}') ?
                                            'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' :
                                            'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white'"
                                        title="{{ $item['name'] }}">
                                        
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="shrink-0 size-5 flex items-center justify-center pointer-events-none">
                                                {!! MenuHelper::getIconSvg($item['icon']) !!}
                                            </span>
                                            <span class="truncate pointer-events-none select-none transition-opacity duration-200"
                                                :class="isOpen() ? 'opacity-100' : 'opacity-0 w-0 hidden'">
                                                {{ $item['name'] }}
                                            </span>
                                        </div>

                                        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"
                                            class="shrink-0 transition-transform duration-200 pointer-events-none"
                                            :class="[
                                                isSubmenuOpen('{{ $menuKey }}') ? 'rotate-180 text-brand-600 dark:text-brand-400' : 'text-gray-400',
                                                isOpen() ? 'block' : 'hidden'
                                            ]">
                                            <path d="M4.792 7.396L10 12.604l5.208-5.208" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>

                                    <!-- Submenu Content (Hanya tampil saat submenu terbuka & sidebar open) -->
                                    <div x-show="isSubmenuOpen('{{ $menuKey }}') && isOpen()"
                                        class="mt-1 ml-5 pl-3 border-l border-gray-200 dark:border-gray-800 space-y-1">
                                        @foreach ($item['subItems'] as $subItem)
                                            <a href="{{ $subItem['path'] }}"
                                                class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                                                :class="isActive('{{ $subItem['path'] }}') ?
                                                    'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 font-semibold' :
                                                    'text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-white/5'">
                                                <span class="truncate pointer-events-none select-none">{{ $subItem['name'] }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <!-- Standalone Item (Dashboard, Settings, etc.) -->
                                    <a href="{{ $item['path'] }}"
                                        class="menu-item flex items-center h-10 px-3 rounded-lg text-sm font-medium transition-colors w-full"
                                        :class="isActive('{{ $item['path'] }}') ?
                                            'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 font-semibold' :
                                            'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white'"
                                        title="{{ $item['name'] }}">
                                        
                                        <span class="shrink-0 size-5 flex items-center justify-center pointer-events-none">
                                            {!! MenuHelper::getIconSvg($item['icon']) !!}
                                        </span>
                                        <span class="ml-3 truncate pointer-events-none select-none transition-opacity duration-200"
                                            :class="isOpen() ? 'opacity-100' : 'opacity-0 w-0 hidden'">
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

    <!-- Bottom User Profile & Actions Footer Section -->
    <div class="p-3 border-t border-gray-100 dark:border-gray-800 flex flex-col gap-2 shrink-0">
        <div class="flex items-center gap-3 px-1.5 py-1">
            <div class="size-8 rounded-full bg-brand-500 text-white flex items-center justify-center text-xs font-bold shrink-0 overflow-hidden"
                title="{{ Auth::user()->name ?? 'User' }}">
                @if(Auth::user()?->avatar_path)
                    <img src="{{ asset('storage/'.Auth::user()->avatar_path) }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                @endif
            </div>
            
            <div class="flex-1 min-w-0 transition-opacity duration-200"
                :class="isOpen() ? 'opacity-100' : 'opacity-0 w-0 hidden'">
                <div class="text-sm font-semibold text-gray-800 dark:text-white truncate">{{ Auth::user()->name ?? 'User' }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email ?? '' }}</div>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="m-0"
                :class="isOpen() ? 'block' : 'hidden'">
                @csrf
                <button type="submit"
                    class="flex items-center justify-center p-1.5 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition cursor-pointer"
                    title="Keluar">
                    <svg class="size-4 shrink-0 pointer-events-none" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" x2="9" y1="12" y2="12"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>
