@extends('layouts.app')

@section('content')
    @php
        $statusLabels = [
            'found' => 'Temuan',
            'clarifying' => 'Klarifikasi',
            'classifying' => 'Klasifikasi',
            'handling' => 'Penanganan',
            'monitoring' => 'Pemantauan',
            'closed' => 'Selesai',
            'dismissed' => 'Dibatalkan',
        ];
        $statusBadges = [
            'found' => 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
            'clarifying' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
            'classifying' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300',
            'handling' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
            'monitoring' => 'bg-violet-50 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
            'closed' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
            'dismissed' => 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300',
        ];
        $isWali = $waliScope !== null;
        $className = $homeroomClasses->pluck('name')->implode(', ');
        $kpi = [
            ['label' => 'Total Siswa', 'value' => $students->count(), 'sub' => 'dalam binaan'],
            ['label' => 'Pelanggaran', 'value' => $caseStats['total'], 'sub' => $caseStats['active'] . ' masih aktif'],
            ['label' => 'Prestasi Terverifikasi', 'value' => $achievementStats['verified'], 'sub' => $achievementStats['pending'] . ' menunggu validasi'],
            ['label' => 'Kasus Selesai', 'value' => $caseStats['closed'], 'sub' => 'ditutup'],
        ];
    @endphp

    <div class="space-y-6">
        <!-- Header Guru -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">{{ $user->name }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if($isWali && $className !== '')
                            Wali Kelas {{ $className }}
                        @elseif($isWali)
                            Wali kelas belum ditetapkan untuk tahun ajaran ini
                        @else
                            Guru &mdash; bukan wali kelas
                        @endif
                        &bull; {{ $activeAcademicYear?->name ?? 'Tahun ajaran belum aktif' }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('kasus-pelanggaran.create') }}"
                        class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 transition">
                        Lapor Kasus
                    </a>
                    @if($isWali)
                        <a href="{{ route('classes.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/5 transition">
                            Pemantauan Kelas
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @unless($isWali)
            <!-- Guru non-wali: hanya bisa melapor -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-800 dark:text-white">Pemantauan kelas tidak tersedia</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Akun Anda belum ditetapkan sebagai wali kelas. Anda tetap dapat melaporkan kasus pelanggaran &mdash;
                    statusnya akan diproses oleh Kesiswaan/BK.
                </p>
            </div>
        @else
            <!-- KPI Pemantauan -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($kpi as $item)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item['label'] }}</p>
                        <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ $item['value'] }}</p>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $item['sub'] }}</p>
                    </div>
                @endforeach
            </div>

            <!-- Daftar Siswa + Poin -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h2 class="text-base font-semibold text-gray-800 dark:text-white">Daftar Siswa &amp; Poin</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Saldo poin terakhir tiap siswa dalam binaan</p>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama</th>
                                <th class="px-5 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Poin</th>
                                <th class="px-5 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($students as $s)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                    <td class="px-5 py-3">
                                        <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $s->full_name }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $s->currentEnrollment?->schoolClass?->name ?? '-' }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="mono text-sm font-semibold text-gray-800 dark:text-white">{{ number_format($s->latest_balance) }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ route('point-ledgers.siswa', $s) }}" class="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-10 text-center">
                                        <p class="text-sm font-semibold text-gray-800 dark:text-white">Belum Ada Siswa Aktif</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Siswa dengan status aktif di kelas ini akan tampil di sini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <!-- Pelanggaran Terbaru -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <div>
                            <h2 class="text-base font-semibold text-gray-800 dark:text-white">Pelanggaran Terbaru</h2>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $caseStats['active'] }} kasus masih aktif</p>
                        </div>
                        <a href="{{ route('kasus-pelanggaran.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">Lihat semua</a>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($recentCases as $case)
                            <a href="{{ route('kasus-pelanggaran.show', $case) }}" class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-gray-800 dark:text-white">{{ $case->student?->full_name ?? 'Siswa' }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $case->case_number }} &bull; {{ $case->violationCategory?->name ?? 'Pelanggaran' }}
                                    </p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusBadges[$case->status] ?? 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300' }}">
                                    {{ $statusLabels[$case->status] ?? $case->status }}
                                </span>
                            </a>
                        @empty
                            <div class="px-5 py-10 text-center">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white">Tidak Ada Catatan Pelanggaran</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Belum ada pelanggaran yang dilaporkan untuk kelas ini.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Prestasi Terbaru -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <div>
                            <h2 class="text-base font-semibold text-gray-800 dark:text-white">Prestasi Terbaru</h2>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $achievementStats['pending'] }} menunggu validasi Kesiswaan/BK</p>
                        </div>
                        <a href="{{ route('achievement-records.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">Lihat semua</a>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($recentAchievements as $record)
                            <a href="{{ route('achievement-records.show', $record) }}" class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-gray-800 dark:text-white">{{ $record->student?->full_name ?? 'Siswa' }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $record->event_name }} &bull; {{ $record->achievementCategory?->name ?? '-' }}
                                    </p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $record->status === 'verified' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : ($record->status === 'rejected' ? 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300') }}">
                                    {{ $record->status === 'verified' ? 'Divalidasi' : ($record->status === 'rejected' ? 'Ditolak' : 'Menunggu validasi') }}
                                </span>
                            </a>
                        @empty
                            <div class="px-5 py-10 text-center">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white">Belum Ada Catatan Prestasi</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Prestasi siswa kelas ini yang terekam akan tampil di sini.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endunless
    </div>
@endsection
