<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Kartu utama --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="glass r-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Siswa Aktif</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $totalStudentsActive }}</p>
                </div>

                <div class="glass r-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Kasus</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $totalCases }}</p>
                </div>

                <div class="glass r-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Kategori Aktif</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $totalCategoriesActive }} / {{ $totalCategories }}</p>
                </div>

                <div class="glass r-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Tahun Ajaran Aktif</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $activeAcademicYear?->name ?? 'Belum ada' }}</p>
                    @if($activeAcademicYear)
                        <p class="mt-1 text-xs text-gray-500">{{ $activeAcademicYear->start_date?->format('d/m/Y') ?? '-' }}</p>
                    @endif
                </div>
            </div>

            {{-- Breakdown status kasus --}}
            <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="glass r-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Ditemukan</p>
                    <p class="mt-1 text-2xl font-semibold text-blue-600">{{ $totalCasesFound }}</p>
                </div>
                <div class="glass r-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Divalidasi</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-600">{{ $totalCasesValidated }}</p>
                </div>
                <div class="glass r-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Selesai</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-700">{{ $totalCasesDone }}</p>
                </div>
                <div class="glass r-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Dibuang</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-400">{{ $totalCasesDismissed }}</p>
                </div>
            </div>

            {{-- Kasus terbaru + siswa poin rendah --}}
            <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6 text-gray-900">
                        <h3 class="font-semibold text-lg mb-4">Kasus Terbaru</h3>
                        <div class="space-y-4">
                            @forelse($recentCases as $case)
                                <div class="glass r-lg p-3">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-medium">{{ $case->student?->full_name ?? 'Siswa tidak dikenal' }}</p>
                                            <p class="text-xs text-gray-500">{{ $case->case_number }}</p>
                                        </div>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $case->status === 'found' ? 'bg-blue-100 text-blue-700' : ($case->status === 'validated' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600') }}">
                                            {{ ucfirst($case->status) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">{{ $case->violationCategory?->name ?? '-' }} ({{ $case->violationCategory?->points ?? 0 }} poin)</p>
                                </div>
                            @empty
                                <p class="text-gray-500">Belum ada kasus.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6 text-gray-900">
                        <h3 class="font-semibold text-lg mb-4">Siswa Poin Rendah</h3>
                        <p class="text-xs text-gray-500 mb-4">Sisa poin <= 500, segera tindak lanjuti pembinaan.</p>
                        <div class="space-y-3">
                            @forelse($lowPointStudents as $student)
                                <div class="glass r-lg p-3">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="text-sm font-medium">{{ $student->full_name }}</p>
                                            <p class="text-xs text-gray-500">{{ $student->schoolClass?->name ?? '-' }} &middot; {{ $student->nis }}</p>
                                        </div>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">{{ $student->latest_balance }} poin</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500">Tidak ada siswa dengan poin kritis.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>