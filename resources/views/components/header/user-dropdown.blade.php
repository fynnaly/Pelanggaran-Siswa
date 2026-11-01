<x-ui.dropdown-menu>
    <x-ui.dropdown-menu.trigger class="dropdown-toggle flex items-center text-gray-700 dark:text-gray-400 cursor-pointer">
        <span class="me-3 h-10 w-10 overflow-hidden rounded-full bg-brand-50 flex items-center justify-center font-bold text-brand-600 dark:bg-brand-950/60 dark:text-brand-400 shrink-0">
            @if(auth()->user()?->avatar_path)
                <img src="{{ asset('storage/'.auth()->user()->avatar_path) }}" alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
            @else
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
            @endif
        </span>

        <span class="block text-sm font-medium">{{ auth()->user()->name ?? 'Pengguna' }}</span>
    </x-ui.dropdown-menu.trigger>

    <x-ui.dropdown-menu.content align="end" :side-offset="12" class="w-65 p-3">
        <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3">
            <span class="h-9 w-9 overflow-hidden rounded-full bg-brand-50 flex items-center justify-center font-bold text-brand-600 dark:bg-brand-950/60 dark:text-brand-400 shrink-0">
                @if(auth()->user()?->avatar_path)
                    <img src="{{ asset('storage/'.auth()->user()->avatar_path) }}" alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                @endif
            </span>
            <div class="min-w-0 flex-1">
                <span class="block text-sm font-medium text-gray-800 dark:text-white truncate">{{ auth()->user()->name ?? 'Pengguna' }}</span>
                <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email ?? '-' }}</span>
            </div>
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
