@php
    $notifications = auth()->user()->notifications()->latest()->take(10)->get();
@endphp

<div class="contents" x-data="{ notifying: {{ $notifications->where('read_at', null)->isNotEmpty() ? 'true' : 'false' }} }">
    <x-ui.dropdown-menu>
        <x-ui.dropdown-menu.trigger as-child>
            <x-ui.button variant="outline" size="icon-lg" class="relative" aria-label="Toggle notifications"
                x-on:click.capture="notifying = false">
                <span x-bind:class="notifying ? 'flex' : 'hidden'"
                    class="absolute end-0 -top-0.5 z-10 h-2 w-2 translate-x-1/2 rounded-full bg-orange-400 rtl:-translate-x-1/2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-orange-400 opacity-75">
                    </span>
                </span>
                <x-svg.bell class="size-auto!" />
            </x-ui.button>
        </x-ui.dropdown-menu.trigger>

        <x-ui.dropdown-menu.content align="end" :side-offset="12"
            class="w-87.5 overflow-hidden p-3 sm:w-90.25 dark:bg-[#1E2635]">
            <div class="flex items-center justify-between pb-3">
                <h5 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ __('header.notifications.title') }}
                </h5>
                <form method="POST" action="{{ route('notifications.mark-read') }}">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        Tandai dibaca
                    </button>
                </form>
            </div>

            <x-ui.dropdown-menu.separator class="my-0" />

            <div class="h-96 overflow-y-auto overflow-x-hidden custom-scrollbar">
                <div class="flex flex-col pe-2">
                    @forelse ($notifications as $notification)
                        @php
                            $data = $notification->data;
                            $url = $data['action_url'] ?? '#';
                        @endphp
                        <x-ui.dropdown-menu.item class="flex items-start gap-3 rounded-lg p-3 hover:bg-gray-100 dark:hover:bg-white/5 {{ $notification->read_at ? 'opacity-60' : '' }}">
                            <x-ui.avatar size="default">
                                <x-ui.avatar.fallback class="bg-brand-500 text-white">
                                    {{ substr($data['student_name'] ?? 'Siswa', 0, 2) }}
                                </x-ui.avatar.fallback>
                            </x-ui.avatar>
                            <a href="{{ $url }}"
                                class="block flex-1 text-start no-underline text-gray-600 dark:text-gray-300">
                                <span class="mb-1.5 block space-x-1 text-sm">
                                    <span class="font-medium text-gray-800 dark:text-white/90">
                                        {{ $data['case_number'] ?? 'Kasus Baru' }}
                                    </span>
                                    <span>
                                        {{ $data['student_name'] ?? 'Siswa' }}
                                    </span>
                                </span>
                                <span class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ $data['violation_code'] ?? '-' }}</span>
                                    <x-svg.dot class="size-2 text-gray-400 dark:text-gray-500" />
                                    <span>{{ $data['violation_name'] ?? 'Pelanggaran' }}</span>
                                </span>
                            </a>
                        </x-ui.dropdown-menu.item>
                        <x-ui.dropdown-menu.separator class="my-0.5 bg-gray-100 last-of-type:hidden dark:bg-gray-700" />
                    @empty
                        <div class="p-4 text-center text-xs text-gray-400">
                            Tidak ada notifikasi
                        </div>
                    @endforelse
                </div>
            </div>

            <x-ui.button as="a" href="{{ route('kasus-pelanggaran.index') }}" variant="outline" size="default"
                class="mt-3 w-full" x-on:click="close()">
                Lihat Semua
            </x-ui.button>
        </x-ui.dropdown-menu.content>
    </x-ui.dropdown-menu>
</div>