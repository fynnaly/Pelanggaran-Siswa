@extends('layouts.app')

@section('content')
    @php
        $resolveRate = $totalCases > 0 ? round(($totalCasesDone / $totalCases) * 100) : 0;
    @endphp

    <div class="space-y-6">
        <!-- Header Page & Fast Actions -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Dashboard Operasional</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $activeAcademicYear?->name ?? 'Tahun ajaran belum aktif' }} &bull; {{ number_format($totalStudentsActive) }} Siswa Aktif
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('kasus-pelanggaran.create') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 transition">
                    + Buat Kasus
                </a>
                <a href="{{ route('achievement-records.create') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition">
                    Rekam Prestasi
                </a>
                <a href="{{ route('students.export') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition">
                    Export Siswa
                </a>
            </div>
        </div>

        <!-- Tier 1: 4 KPI Cards TailAdmin Style -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 md:gap-6">
            <!-- Card 1: Perlu Verifikasi -->
            <a href="{{ route('kasus-pelanggaran.index', ['status' => 'found']) }}"
                class="rounded-2xl border border-gray-200 bg-white p-5 hover:border-brand-500/30 transition shadow-theme-xs dark:border-gray-800 dark:bg-white/5 block">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase font-medium tracking-wider text-gray-500 dark:text-gray-400">Perlu Verifikasi</span>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <span class="text-3xl font-bold tracking-tight {{ $totalCasesFound > 0 ? 'text-amber-500' : 'text-gray-800 dark:text-white' }}">
                        {{ number_format($totalCasesFound) }}
                    </span>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ $totalCasesFound > 0 ? 'Kasus temuan menunggu tindakan' : 'Tidak ada antrean temuan baru' }}
                </p>
            </a>

            <!-- Card 2: Dalam Penanganan -->
            <a href="{{ route('kasus-pelanggaran.index', ['status' => 'handling']) }}"
                class="rounded-2xl border border-gray-200 bg-white p-5 hover:border-brand-500/30 transition shadow-theme-xs dark:border-gray-800 dark:bg-white/5 block">
                <div class="flex items-center justify-between mb-6">
                    <span class="text-xs uppercase font-medium tracking-wider text-gray-500 dark:text-gray-400">Dalam Penanganan</span>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <span class="text-3xl font-bold tracking-tight text-brand-500">
                        {{ number_format($totalCasesValidated) }}
                    </span>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Pembinaan aktif berjalan
                </p>
            </a>

            <!-- Card 3: Intervensi Khusus -->
            <a href="{{ route('monitor-poin.index', ['zone' => 'Intervensi Khusus']) }}"
                class="rounded-2xl border border-gray-200 bg-white p-5 hover:border-brand-500/30 transition shadow-theme-xs dark:border-gray-800 dark:bg-white/5 block"
                title="Filter siswa zona poin kritis">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase font-medium tracking-wider text-gray-500 dark:text-gray-400">Intervensi Khusus</span>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <span class="text-3xl font-bold tracking-tight {{ $criticalStudentsCount > 0 ? 'text-red-500' : 'text-emerald-500' }}">
                        {{ number_format($criticalStudentsCount) }}
                    </span>
                </div>
                <p class="mt-2 text-xs {{ $criticalStudentsCount > 0 ? 'text-red-500' : 'text-gray-500 dark:text-gray-400' }}">
                    {{ $criticalStudentsCount > 0 ? 'Siswa di batas zona kritis (< 1500)' : 'Seluruh siswa di batas aman' }}
                </p>
            </a>

            <!-- Card 4: Kasus Selesai -->
            <a href="{{ route('kasus-pelanggaran.index', ['status' => 'closed']) }}"
                class="rounded-2xl border border-gray-200 bg-white p-5 hover:border-brand-500/30 transition shadow-theme-xs dark:border-gray-800 dark:bg-white/5 block">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase font-medium tracking-wider text-gray-500 dark:text-gray-400">Kasus Selesai</span>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <span class="text-3xl font-bold tracking-tight text-gray-800 dark:text-white">
                        {{ number_format($totalCasesDone) }}
                    </span>
                    <span class="text-xs font-semibold text-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-full">
                        {{ $resolveRate }}%
                    </span>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Selesai dari total {{ number_format($totalCases) }} kasus
                </p>
            </a>
        </div>

        <!-- Tier 2: Operasional Harian (Kasus Terbaru & Siswa Kritis) -->
        <div class="grid grid-cols-12 gap-4 md:gap-6">
            <!-- Kolom Kiri: Tabel Kasus Terbaru -->
            <div class="col-span-12 xl:col-span-8 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/5 shadow-theme-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-white">Kasus Terbaru</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Antrean pencatatan dan eskalasi disiplin</p>
                    </div>
                    <a href="{{ route('kasus-pelanggaran.index') }}"
                        class="text-xs font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400">
                        Lihat Semua &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="border-b border-gray-100 bg-gray-50/50 text-xs uppercase font-semibold text-gray-400 dark:border-gray-800 dark:bg-white/2">
                            <tr>
                                <th class="px-4 py-3">No. Kasus</th>
                                <th class="px-4 py-3">Siswa</th>
                                <th class="px-4 py-3">Pelanggaran</th>
                                <th class="px-4 py-3">Poin</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($recentCases as $case)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-white/2 transition">
                                    <td class="px-4 py-3 font-mono text-xs font-medium text-gray-800 dark:text-white">
                                        <a href="{{ route('kasus-pelanggaran.show', $case) }}" class="hover:text-brand-500">
                                            {{ $case->case_number ?? ('#' . $case->id) }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-800 dark:text-white">
                                            {{ $case->student?->full_name ?? $case->student?->name ?? '-' }}
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            {{ $case->schoolClass?->name ?? $case->student?->currentEnrollment?->schoolClass?->name ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 max-w-[200px] truncate" title="{{ $case->violationCategory?->name ?? '-' }}">
                                        {{ $case->violationCategory?->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 font-mono font-semibold text-red-500">
                                        -{{ $case->getEffectivePoints() }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $statusMap = [
                                                'found' => ['label' => 'Temuan', 'class' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-900/40'],
                                                'handling' => ['label' => 'Penanganan', 'class' => 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400 border border-blue-200 dark:border-blue-900/40'],
                                                'monitoring' => ['label' => 'Monitoring', 'class' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-900/40'],
                                                'closed' => ['label' => 'Selesai', 'class' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/40'],
                                                'dismissed' => ['label' => 'Dibatalkan', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700'],
                                            ];
                                            $st = $statusMap[$case->status] ?? ['label' => $case->status, 'class' => 'bg-gray-100 text-gray-600'];
                                        @endphp
                                        <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $st['class'] }}">
                                            {{ $st['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-400">
                                        {{ ($case->incident_date ?? $case->created_at)?->format('d/m/Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-xs text-gray-400">
                                        Belum ada kasus pelanggaran yang tercatat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Kolom Kanan: Siswa Butuh Perhatian (Intervensi Khusus) -->
            <div class="col-span-12 xl:col-span-4 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/5 shadow-theme-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-white">Poin Kritis</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Prioritas pembinaan karakter</p>
                    </div>
                    <a href="{{ route('monitor-poin.index', ['zone' => 'Intervensi Khusus']) }}"
                        class="text-xs font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400">
                        Filter Zona &rarr;
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse($lowPointStudents as $student)
                        <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 bg-gray-50/50 dark:border-gray-800 dark:bg-white/2">
                            <div class="min-w-0 pr-3">
                                <a href="{{ route('students.show', $student) }}" class="font-medium text-sm text-gray-800 dark:text-white hover:text-brand-500 block truncate">
                                    {{ $student->full_name ?? $student->name }}
                                </a>
                                <span class="text-xs text-gray-400 block truncate">
                                    {{ $student->currentEnrollment?->schoolClass?->name ?? 'Tanpa Kelas' }} &bull; NISN: {{ $student->nisn ?? '-' }}
                                </span>
                            </div>
                            <div class="text-end shrink-0">
                                <span class="font-mono text-sm font-bold text-red-500 block">
                                    {{ $student->latest_balance }}
                                </span>
                                <span class="text-[10px] text-gray-400 uppercase tracking-wider block">Poin</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center text-xs text-gray-400">
                            Semua siswa berada di atas zona intervensi khusus.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Tier 3: Analitik Tren & Sebaran Zona Poin -->
        <div class="grid grid-cols-12 gap-4 md:gap-6">
            <!-- Grafik Tren Kasus 30 Hari -->
            <div class="col-span-12 xl:col-span-7 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/5 shadow-theme-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-white">Tren Kasus 30 Hari</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Perbandingan kasus baru dan selesai</p>
                    </div>
                </div>
                <div class="h-64 relative">
                    <canvas id="trendChart"
                        data-labels='@json($trendLabels)'
                        data-new='@json($trendNew)'
                        data-done='@json($trendDone)'></canvas>
                </div>
            </div>

            <!-- Distribusi 8 Zona Poin -->
            <div class="col-span-12 xl:col-span-5 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/5 shadow-theme-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-white">Distribusi Zona Poin</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Sebaran pembinaan seluruh siswa aktif</p>
                    </div>
                </div>
                <div class="space-y-2.5">
                    @foreach($zoneCounts as $zoneName => $info)
                        @php
                            $pct = $maxZoneCount > 0 ? round(($info['count'] / $maxZoneCount) * 100) : 0;
                            $hex = match($info['color'] ?? 'primary') {
                                'danger' => '#ef4444',
                                'warning' => '#f59e0b',
                                'info' => '#3b82f6',
                                'primary' => '#465fff',
                                default => '#10b981',
                            };
                        @endphp
                        <a href="{{ route('monitor-poin.index', ['zone' => $zoneName]) }}"
                            class="group block p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-white/2 transition">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-medium text-gray-700 dark:text-gray-300 group-hover:text-brand-500">
                                    {{ $zoneName }} <span class="text-gray-400 text-[11px]">({{ $info['range'] }})</span>
                                </span>
                                <span class="font-mono font-bold text-gray-800 dark:text-white">{{ $info['count'] }}</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-300"
                                    style="width: {{ $pct }}%; background-color: {{ $hex }}"></div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" integrity="sha384-e6nUZLBkQ86NJ6TVVKAeSaK8jWa3NhkYWZFomE39AvDbQWeie9PlQqM3pmYW5d1g" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('trendChart');
            if (!canvas) return;

            const labels = JSON.parse(canvas.dataset.labels || '[]');
            const dataNew = JSON.parse(canvas.dataset.new || '[]');
            const dataDone = JSON.parse(canvas.dataset.done || '[]');

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Kasus Baru',
                            data: dataNew,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            tension: 0.3,
                            fill: true,
                            pointRadius: 2,
                        },
                        {
                            label: 'Kasus Selesai',
                            data: dataDone,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.3,
                            fill: true,
                            pointRadius: 2,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { boxWidth: 12, font: { family: 'Outfit', size: 12 } }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { maxTicksLimit: 10, maxRotation: 0 }
                        },
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        });
    </script>
    @endpush
@endsection
