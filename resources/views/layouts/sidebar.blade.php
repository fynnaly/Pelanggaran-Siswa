@php
    use App\Helpers\MenuHelper;
    $menuGroups = MenuHelper::getMenuGroups();

    // Get current path
    $currentPath = request()->path();
@endphp

<aside id="sidebar"
    class="fixed flex flex-col mt-0 top-0 start-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-50 border-r border-gray-200"
    :class="{
        'w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen,
        'w-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen,
        'translate-x-0': $store.sidebar.isMobileOpen,
        'max-xl:-translate-x-full': !$store.sidebar.isMobileOpen
    }"
    x-data="{
        openSubmenus: {},
        init() {
            this.initializeActiveMenus();
        },
        initializeActiveMenus() {
            const currentPath = '{{ $currentPath }}';

            @foreach ($menuGroups as $groupIndex => $menuGroup)
                @foreach ($menuGroup['items'] as $itemIndex => $item)
                    @if (isset($item['subItems']))
                        @foreach ($item['subItems'] as $subItem)
                            if (currentPath === '{{ ltrim($subItem['path'], '/') }}' || window.location.pathname === '{{ $subItem['path'] }}') {
                                this.openSubmenus['{{ $groupIndex }}-{{ $itemIndex }}'] = true;
                            }
                        @endforeach
                    @endif
                @endforeach
            @endforeach
        },
        toggleSubmenu(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            this.openSubmenus[key] = !this.openSubmenus[key];
            this.openSubmenus = Object.assign({}, this.openSubmenus);
        },
        isSubmenuOpen(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            return this.openSubmenus[key] || false;
        },
        isActive(path) {
            return window.location.pathname === path || '{{ $currentPath }}' === path.replace(/^\//, '');
        }
    }"
    @mouseenter="if (!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">

    <!-- Logo Section -->
    <div class="px-5 py-6 flex items-center border-b border-gray-100 dark:border-gray-800/80"
        :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'justify-center' : 'justify-between'">
        <a href="/" class="flex items-center gap-3">
            <img class="h-8 w-8 shrink-0 rounded-lg" src="/image.ico" alt="Logo" />
            <div x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="overflow-hidden">
                <span class="block font-bold text-gray-800 dark:text-white text-base leading-tight truncate">Pelanggaran Kuy</span>
            </div>
        </a>
    </div>

    <!-- Navigation Menu Content (Matches TailAdmin Exact Flow) -->
    <div class="flex-1 flex flex-col justify-between overflow-y-auto no-scrollbar px-5 py-6">
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
                                    <button type="button" @click="openSubmenus['{{ $groupIndex }}-{{ $itemIndex }}'] = !openSubmenus['{{ $groupIndex }}-{{ $itemIndex }}']"
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
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 -translate-y-1"
                                        x-transition:enter-end="opacity-100 translate-y-0"
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
</aside>
