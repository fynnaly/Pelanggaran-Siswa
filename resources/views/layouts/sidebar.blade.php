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
    class="fixed flex flex-col mt-0 top-0 start-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-[width] duration-200 ease-in-out z-110 border-r border-gray-200"
    :class="{
        'w-[260px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen,
        'w-[64px]': !$store.sidebar.isExpanded && !$store.sidebar.isMobileOpen,
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
        isActive(path) {
            return window.location.pathname === path || '{{ $currentPath }}' === path.replace(/^\//, '');
        },
        isGroupActive(subItems) {
            if (!subItems) return false;
            return subItems.some(sub => this.isActive(sub.path));
        }
    }">

    <!-- Logo & Brand Header Section (Cloudflare Style) -->
    <div class="h-16 flex items-center border-b border-gray-100 dark:border-gray-800 shrink-0"
        :class="($store.sidebar.isExpanded || $store.sidebar.isMobileOpen) ? 'px-4 justify-between' : 'px-2 justify-center'">
        
        <a href="/" class="flex items-center gap-3 min-w-0" :class="($store.sidebar.isExpanded || $store.sidebar.isMobileOpen) ? '' : 'justify-center'">
            <img class="h-8 w-8 shrink-0 rounded-lg" src="/image.ico" alt="Logo" />
            <div x-show="$store.sidebar.isExpanded || $store.sidebar.isMobileOpen" class="overflow-hidden">
                <span class="block font-bold text-gray-800 dark:text-white text-base leading-tight truncate">Pelanggaran Kuy</span>
            </div>
        </a>

        <!-- Cloudflare Sidebar Toggle Trigger Button -->
        <button type="button" @click.stop="$store.sidebar.toggleExpanded()"
            x-show="$store.sidebar.isExpanded || $store.sidebar.isMobileOpen"
            class="hidden xl:inline-flex p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-800 transition cursor-pointer"
            title="Kecilkan sidebar">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                <path d="M9 3v18"></path>
            </svg>
        </button>
    </div>

    <!-- Navigation Menu Content Container -->
    <div x-ref="menuContainer" id="sidebarMenuContainer"
        class="flex-1 flex flex-col justify-between overflow-y-auto overflow-x-hidden no-scrollbar py-4"
        :class="($store.sidebar.isExpanded || $store.sidebar.isMobileOpen) ? 'px-3' : 'px-2'">
        
        <nav class="space-y-6">
            @foreach ($menuGroups as $groupIndex => $menuGroup)
                <div class="flex flex-col gap-1.5">
                    <!-- Menu Group Label -->
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 px-3 mb-1"
                        x-show="$store.sidebar.isExpanded || $store.sidebar.isMobileOpen">
                        {{ $menuGroup['title'] }}
                    </div>
                    <div x-show="!$store.sidebar.isExpanded && !$store.sidebar.isMobileOpen" class="w-6 h-px mx-auto bg-gray-200 dark:bg-gray-800 my-1.5"></div>

                    <!-- Menu List -->
                    <ul class="flex flex-col gap-1">
                        @foreach ($menuGroup['items'] as $itemIndex => $item)
                            @php $menuKey = "{$groupIndex}-{$itemIndex}"; @endphp
                            <li class="relative">
                                @if (isset($item['subItems']))
                                    <!-- EXPANDED MODE: Collapsible Menu Trigger & Accordion -->
                                    <div x-show="$store.sidebar.isExpanded || $store.sidebar.isMobileOpen">
                                        <button type="button" @click="toggleSubmenu({{ $groupIndex }}, {{ $itemIndex }})"
                                            class="menu-item cursor-pointer group flex items-center justify-between w-full px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                                            :class="isGroupActive(@js($item['subItems'])) || openSubmenus['{{ $menuKey }}'] ?
                                                'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' :
                                                'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white'"
                                            title="{{ $item['name'] }}">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <span class="shrink-0 size-5 flex items-center justify-center pointer-events-none">
                                                    {!! MenuHelper::getIconSvg($item['icon']) !!}
                                                </span>
                                                <span class="truncate pointer-events-none select-none">{{ $item['name'] }}</span>
                                            </div>

                                            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"
                                                class="shrink-0 transition-transform duration-200 pointer-events-none"
                                                :class="openSubmenus['{{ $menuKey }}'] ? 'rotate-180 text-brand-600 dark:text-brand-400' : 'text-gray-400'">
                                                <path d="M4.792 7.396L10 12.604l5.208-5.208" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>

                                        <!-- Collapsible Panel Content (Tree Hierarchy) -->
                                        <div x-show="openSubmenus['{{ $menuKey }}']" class="mt-1 ml-5 pl-3 border-l border-gray-200 dark:border-gray-800 space-y-1">
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
                                    </div>

                                    <!-- COLLAPSED MODE: Direct Link ke Sub-item Pertama -->
                                    <div x-show="!$store.sidebar.isExpanded && !$store.sidebar.isMobileOpen">
                                        <a href="{{ $item['subItems'][0]['path'] }}"
                                            class="flex items-center justify-center size-10 mx-auto rounded-lg transition-colors"
                                            :class="isGroupActive(@js($item['subItems'])) ?
                                                'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' :
                                                'text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-white/5'"
                                            title="{{ $item['name'] }}">
                                            <span class="shrink-0 size-5 flex items-center justify-center pointer-events-none">
                                                {!! MenuHelper::getIconSvg($item['icon']) !!}
                                            </span>
                                        </a>
                                    </div>
                                @else
                                    <!-- Standalone Item (Dashboard, Settings, etc.) -->
                                    <div x-show="$store.sidebar.isExpanded || $store.sidebar.isMobileOpen">
                                        <a href="{{ $item['path'] }}"
                                            class="menu-item flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors w-full"
                                            :class="isActive('{{ $item['path'] }}') ?
                                                'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 font-semibold' :
                                                'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white'"
                                            title="{{ $item['name'] }}">
                                            <span class="shrink-0 size-5 flex items-center justify-center pointer-events-none">
                                                {!! MenuHelper::getIconSvg($item['icon']) !!}
                                            </span>
                                            <span class="truncate pointer-events-none select-none">{{ $item['name'] }}</span>
                                        </a>
                                    </div>

                                    <!-- Standalone Item saat Collapsed -->
                                    <div x-show="!$store.sidebar.isExpanded && !$store.sidebar.isMobileOpen">
                                        <a href="{{ $item['path'] }}"
                                            class="flex items-center justify-center size-10 mx-auto rounded-lg transition-colors"
                                            :class="isActive('{{ $item['path'] }}') ?
                                                'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' :
                                                'text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-white/5'"
                                            title="{{ $item['name'] }}">
                                            <span class="shrink-0 size-5 flex items-center justify-center pointer-events-none">
                                                {!! MenuHelper::getIconSvg($item['icon']) !!}
                                            </span>
                                        </a>
                                    </div>
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
        <!-- Expanded Profile View -->
        <div x-show="$store.sidebar.isExpanded || $store.sidebar.isMobileOpen" class="flex flex-col gap-2">
            <div class="flex items-center gap-3 px-2 py-1.5">
                <div class="size-8 rounded-full bg-brand-500 text-white flex items-center justify-center text-xs font-bold shrink-0 overflow-hidden"
                    title="{{ Auth::user()->name ?? 'User' }}">
                    @if(Auth::user()?->avatar_path)
                        <img src="{{ asset('storage/'.Auth::user()->avatar_path) }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                    @else
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold text-gray-800 dark:text-white truncate">{{ Auth::user()->name ?? 'User' }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email ?? '' }}</div>
                </div>
            </div>

            <div class="flex items-center gap-1 pt-1 border-t border-gray-100 dark:border-gray-800/60">
                <a href="{{ route('profile.edit') }}"
                    class="flex-1 flex items-center justify-center gap-2 py-1.5 px-2 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 transition"
                    title="Pengaturan Akun">
                    <svg class="size-4 shrink-0 text-gray-500 dark:text-gray-400 pointer-events-none" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                    </svg>
                    <span>Profil</span>
                </a>

                <form method="POST" action="{{ route('logout') }}" class="m-0 flex-1">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 py-1.5 px-2 rounded-lg text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition cursor-pointer"
                        title="Keluar">
                        <svg class="size-4 shrink-0 text-red-600 dark:text-red-400 pointer-events-none" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" x2="9" y1="12" y2="12"/>
                        </svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Collapsed Profile View -->
        <div x-show="!$store.sidebar.isExpanded && !$store.sidebar.isMobileOpen" class="flex flex-col items-center gap-2">
            <div class="size-9 rounded-full bg-brand-500 text-white flex items-center justify-center text-xs font-bold overflow-hidden"
                title="{{ Auth::user()->name ?? 'User' }}">
                @if(Auth::user()?->avatar_path)
                    <img src="{{ asset('storage/'.Auth::user()->avatar_path) }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                @endif
            </div>

            <a href="{{ route('profile.edit') }}"
                class="flex items-center justify-center size-9 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-white/5 transition"
                title="Pengaturan Akun">
                <svg class="size-4.5 shrink-0 pointer-events-none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                </svg>
            </a>

            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit"
                    class="flex items-center justify-center size-9 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition cursor-pointer"
                    title="Keluar">
                    <svg class="size-4.5 shrink-0 pointer-events-none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" x2="9" y1="12" y2="12"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>
