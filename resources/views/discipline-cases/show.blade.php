<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Kasus Pelanggaran</h2>
            <a href="{{ route('discipline-cases.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200">Kembali</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
                <div class="mb-4 p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm">{{ session('warning') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
            @endif

            @php
                $statusColors = [
                    'found' => 'bg-blue-100 text-blue-700',
                    'validated' => 'bg-emerald-100 text-emerald-700',
                    'dismissed' => 'bg-gray-100 text-gray-600',
                    'done' => 'bg-gray-200 text-gray-700',
                ];
            @endphp

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Nomor Kasus</p>
                            <p class="mt-1 text-sm font-semibold font-mono text-gray-900">{{ $disciplineCase->case_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Siswa</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $disciplineCase->student?->full_name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Pelanggaran</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $disciplineCase->violationCategory?->name ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Poin</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $disciplineCase->violationCategory?->points ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Status</p>
                            <p class="mt-1">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusColors[$disciplineCase->status] ?? '' }}">{{ $disciplineCase->status }}</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Lokasi</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $disciplineCase->location ?? '-' }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Deskripsi</p>
                        <div class="mt-2 p-4 rounded-xl bg-gray-50 border border-gray-200 text-sm text-gray-800 whitespace-pre-wrap">{{ $disciplineCase->description ?? '-' }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Dilaporkan Oleh</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $disciplineCase->reporter?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Divalidasi Oleh</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $disciplineCase->validator?->name ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Waktu Lapor</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $disciplineCase->created_at?->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Waktu Validasi</p>
                            <p class="mt-1 text-sm text-gray-900">{{ $disciplineCase->validated_at?->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                @if($disciplineCase->status === 'found' || $disciplineCase->status === 'validated')
                    <div class="border-t border-gray-200 px-6 py-4 flex flex-wrap gap-3 justify-end">
                        @if($disciplineCase->status === 'found')
                            <form action="{{ route('discipline-cases.validate', $disciplineCase) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="validation_passed" value="1">
                                <button class="px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700">Validasi</button>
                            </form>
                            <form action="{{ route('discipline-cases.validate', $disciplineCase) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="validation_passed" value="0">
                                <button class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200">Tolak</button>
                            </form>
                        @endif

                        @if($disciplineCase->status === 'validated')
                            <form action="{{ route('discipline-cases.done', $disciplineCase) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <button class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700">Selesai</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
