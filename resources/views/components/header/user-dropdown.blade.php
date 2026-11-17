<x-ui.dropdown-menu>
    <x-ui.dropdown-menu.trigger class="dropdown-toggle flex items-center text-gray-700 dark:text-gray-400 cursor-pointer">
        <span class="me-3 h-10 w-10 overflow-hidden rounded-full bg-transparent border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 flex items-center justify-center font-semibold shrink-0">
            <img src="{{ auth()->user()?->avatar_url ?? asset('assets/images/user/default-avatar.png') }}" alt="{{ auth()->user()->name ?? 'Pengguna' }}" class="w-full h-full object-cover">
        </span>

        <span class="block text-sm font-medium">{{ auth()->user()->name ?? 'Pengguna' }}</span>
    </x-ui.dropdown-menu.trigger>

    <x-ui.dropdown-menu.content align="end" :side-offset="12" class="w-65 p-3">
        <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3">
            <span class="h-9 w-9 overflow-hidden rounded-full bg-transparent border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 flex items-center justify-center font-semibold shrink-0">
                <img src="{{ auth()->user()?->avatar_url ?? asset('assets/images/user/default-avatar.png') }}" alt="{{ auth()->user()->name ?? 'Pengguna' }}" class="w-full h-full object-cover">
            </span>
            <div class="min-w-0 flex-1">
                <span class="block text-sm font-medium text-gray-800 dark:text-white truncate">{{ auth()->user()->name ?? 'Pengguna' }}</span>
                <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email ?? '-' }}</span>
            </div>
        </div>

        <x-ui.dropdown-menu.separator />

        <x-ui.dropdown-menu.item to="{{ route('profile.edit') }}">
            <x-svg.user-circle class="size-5" />
            {{ auth()->user()?->hasRole('siswa') ? 'Profil Saya' : 'Edit Profil' }}
        </x-ui.dropdown-menu.item>

        <x-ui.dropdown-menu.separator />

        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
        <x-ui.dropdown-menu.item variant="destructive" x-on:click.capture="document.getElementById('logout-form').submit()">
            <x-svg.log-out class="size-5" />
            Keluar
        </x-ui.dropdown-menu.item>
    </x-ui.dropdown-menu.content>
</x-ui.dropdown-menu>
