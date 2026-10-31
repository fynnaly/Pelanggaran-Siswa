<x-ui.dropdown-menu>
    <x-ui.dropdown-menu.trigger class="dropdown-toggle flex items-center text-gray-700 dark:text-gray-400 cursor-pointer">
        <span class="me-3 h-10 w-10 overflow-hidden rounded-full bg-brand-50 flex items-center justify-center font-bold text-brand-600 dark:bg-brand-950/60 dark:text-brand-400">
            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
        </span>

        <span class="me-1 block text-sm font-medium">{{ auth()->user()->name ?? 'Pengguna' }}</span>

        <x-svg.chevron-down class="size-5 text-gray-500 duration-200 dark:text-gray-400" x-bind:class="open ? 'rotate-180' : 'rotate-0'" />
    </x-ui.dropdown-menu.trigger>

    <x-ui.dropdown-menu.content align="end" :side-offset="12" class="w-65 p-3">
        <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-800">
            <span class="block text-sm font-medium text-gray-800 dark:text-white">{{ auth()->user()->name ?? 'Pengguna' }}</span>
            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email ?? '-' }}</span>
        </div>

        <x-ui.dropdown-menu.separator />

        <x-ui.dropdown-menu.item to="{{ route('profile.edit') }}">
            <x-svg.user-circle class="size-5" />
            Edit Profil
        </x-ui.dropdown-menu.item>

        <x-ui.dropdown-menu.separator />

        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
        <x-ui.dropdown-menu.item variant="destructive" x-on:click.capture="document.getElementById('logout-form').submit()">
            <x-svg.log-out />
            {{ __('userDropdown.signOut') }}
        </x-ui.dropdown-menu.item>
    </x-ui.dropdown-menu.content>
</x-ui.dropdown-menu>
