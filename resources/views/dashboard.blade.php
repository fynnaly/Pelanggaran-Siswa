<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Dashboard</h1>
                <p class="text-muted text-sm">
                    {{ $activeAcademicYear?->name ?? 'Tahun ajaran belum aktif' }} &middot; {{ number_format($totalStudentsActive) }} siswa aktif
                </p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('kasus-pelanggaran.create') }}" class="btn btn-primary btn-sm">Buat Kasus</a>
                <a href="{{ route('achievement-records.create') }}" class="btn btn-secondary btn-sm">Rekam Prestasi</a>
                <a href="{{ route('students.export') }}" class="btn btn-secondary btn-sm">Export Siswa</a>
            </div>
        </div>
    </x-slot>

    @php
        $resolveRate = $totalCases > 0 ? round(($totalCasesDone / $totalCases) * 100) : 0;
    @endphp

    <div class="main-wrap">
        {{-- Tier 1: Indikator Operasional Prioritas --}}
        <div class="grid grid-4">
            <a href="{{ route('kasus-pelanggaran.index', ['status' => 'found']) }}" class="card-glass" style="display:block;text-decoration:none;color:inherit">
                <div class="stat-label">Perlu Verifikasi</div>
                <div class="stat-value" style="color:{{ $totalCasesFound > 0 ? 'var(--accent)' : 'var(--on-surface)' }}">
                    {{ number_format($totalCasesFound) }}
                </div>
                <div class="stat-change {{ $totalCasesFound > 0 ? 'negative' : 'positive' }}">
                    {{ $totalCasesFound > 0 ? 'Kasus temuan menunggu tindak lanjut' : 'Tidak ada antrean temuan baru' }}
                </div>
            </a>

            <a href="{{ route('kasus-pelanggaran.index', ['status' => 'handling']) }}" class="card-glass" style="display:block;text-decoration:none;color:inherit">
                <div class="stat-label">Dalam Penanganan</div>
                <div class="stat-value" style="color:{{ $totalCasesValidated > 0 ? 'var(--primary)' : 'var(--on-surface)' }}">
                    {{ number_format($totalCasesValidated) }}
                </div>
                <div class="stat-change" style="color:var(--on-surface-muted)">
                    Pembinaan aktif berjalan
                </div>
            </a>

            <a href="{{ route('monitor-poin.index', ['zone' => 'Intervensi Khusus']) }}" class="card-glass" style="display:block;text-decoration:none;color:inherit" title="Klik untuk memfilter daftar siswa di zona Intervensi Khusus">
                <div class="stat-label">Intervensi Khusus</div>
                <div class="stat-value" style="color:{{ $criticalStudentsCount > 0 ? 'var(--danger)' : 'var(--tertiary)' }}">
                    {{ number_format($criticalStudentsCount) }}
                </div>
                <div class="stat-change {{ $criticalStudentsCount > 0 ? 'negative' : 'positive' }}">
                    {{ $criticalStudentsCount > 0 ? 'Siswa berada di zona poin kritis' : 'Seluruh siswa di batas aman' }}
                </div>
            </a>

            <a href="{{ route('kasus-pelanggaran.index', ['status' => 'closed']) }}" class="card-glass" style="display:block;text-decoration:none;color:inherit">
                <div class="stat-label">Kasus Selesai</div>
                <div class="stat-value">{{ number_format($totalCasesDone) }}</div>
                <div class="stat-change positive">
                    {{ $resolveRate }}% selesai dari {{ number_format($totalCases) }} kasus
                </div>
            </a>
        </div>

        {{-- Tier 2: Operasional Harian & Peringatan Dini --}}
        <div class="section">
            <div class="layout-2col">
                {{-- Kiri (Utama): Kasus Terbaru + Progres Penanganan --}}
                <div style="min-width:0">
                    <div class="section-header">
                        <h2 style="font-size:1.125rem;margin:0">Kasus Terbaru</h2>
                        <a href="{{ route('kasus-pelanggaran.index') }}" class="btn btn-secondary btn-sm">Lihat Semua</a>
                    </div>

                    @if($totalCases > 0)
                        @php
                            $total = max($totalCases, 1);
                            $segs = [
                                ['label' => 'Temuan', 'count' => $totalCasesFound, 'bg' => 'var(--accent)'],
                                ['label' => 'Penanganan', 'count' => $totalCasesValidated, 'bg' => 'var(--primary)'],
                                ['label' => 'Selesai', 'count' => $totalCasesDone, 'bg' => 'var(--tertiary)'],
                                ['label' => 'Dibuang', 'count' => $totalCasesDismissed, 'bg' => 'var(--on-surface-muted)'],
                            ];
                        @endphp
                        <div class="card no-hover" style="padding:var(--sp-md);margin-bottom:var(--sp-md)">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:var(--sp-sm);margin-bottom:8px">
                                <span style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--on-surface-muted)">Progres Penanganan Kasus</span>
                                <span class="mono" style="font-size:.75rem;color:var(--on-surface-muted)">{{ number_format($totalCases) }} kasus</span>
                            </div>
                            <div style="height:10px;background:var(--surface);border-radius:var(--r-full);overflow:hidden;display:flex;margin-bottom:8px">
                                @foreach($segs as $s)
                                    @if($s['count'] > 0)
                                        <div style="width:{{ ($s['count'] / $total) * 100 }}%;height:100%;background:{{ $s['bg'] }}" title="{{ $s['label'] }}: {{ $s['count'] }}"></div>
                                    @endif
                                @endforeach
                            </div>
                            <div style="display:flex;flex-wrap:wrap;gap:var(--sp-md);font-size:.75rem;color:var(--on-surface-muted)">
                                @foreach($segs as $s)
                                    <span style="display:inline-flex;align-items:center;gap:6px">
                                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $s['bg'] }};display:inline-block"></span>
                                        {{ $s['label'] }}: <strong style="color:var(--on-surface)">{{ $s['count'] }}</strong>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>No. Kasus</th>
                                    <th>Siswa</th>
                                    <th>Pelanggaran</th>
                                    <th>Poin</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentCases as $case)
                                    @php
                                        $effectivePoints = $case->getEffectivePoints();
                                        $className = $case->schoolClass?->name ?? $case->student?->currentEnrollment?->schoolClass?->name ?? '';
                                        $incDate = $case->incident_date ?? $case->created_at;
                                    @endphp
                                    <tr>
                                        <td class="mono">
                                            <a href="{{ route('kasus-pelanggaran.show', $case) }}" style="font-weight:600;color:var(--primary)">
                                                {{ $case->case_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <strong>{{ $case->student?->full_name ?? '-' }}</strong>
                                            @if($className)
                                                <br><span class="text-muted text-sm">{{ $className }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $case->violationCategory?->name ?? '-' }}</td>
                                        <td class="mono" style="color:var(--danger);font-weight:600">-{{ $effectivePoints }}</td>
                                        <td>
                                            @if($case->status === 'found')
                                                <span class="badge badge-warning">Temuan</span>
                                            @elseif(in_array($case->status, ['clarifying', 'classifying', 'handling', 'monitoring'], true))
                                                <span class="badge badge-info">Penanganan</span>
                                            @elseif($case->status === 'closed')
                                                <span class="badge badge-success">Selesai</span>
                                            @else
                                                <span class="badge badge-neutral">Dibuang</span>
                                            @endif
                                        </td>
                                        <td class="text-sm text-muted" style="white-space:nowrap">{{ $incDate?->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="padding:36px var(--sp-md);text-align:center;color:var(--on-surface-muted)">
                                            Belum ada kasus pelanggaran tercatat.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Kanan (Peringatan Dini): Siswa Poin Terendah --}}
                <div style="min-width:0">
                    <div class="section-header">
                        <h2 style="font-size:1.125rem;margin:0">Siswa Poin Kritis</h2>
                        <a href="{{ route('monitor-poin.index', ['zone' => 'Intervensi Khusus']) }}" class="btn btn-secondary btn-sm">Lihat Semua Kritis</a>
                    </div>
                    <div class="card no-hover">
                        @forelse($lowPointStudents as $idx => $student)
                            @php
                                $initials = collect(explode(' ', $student->full_name))->map(fn ($w) => strtoupper(substr($w, 0, 1)))->take(2)->implode('');
                                $bal = (int) ($student->latest_balance ?? 0);
                                $zone = ['name' => 'Intervensi Khusus', 'color' => 'danger'];
                                foreach (config('zones.zones', []) as $_z) {
                                    $minOk = $_z['min'] === null || $bal >= $_z['min'];
                                    $maxOk = $_z['max'] === null || $bal <= $_z['max'];
                                    if ($minOk && $maxOk) { $zone = $_z; break; }
                                }
                                $zoneBg = match ($zone['color'] ?? 'danger') {
                                    'primary' => 'var(--primary)',
                                    'success' => 'var(--bs)',
                                    'info'    => 'var(--primary)',
                                    'warning' => 'var(--ws)',
                                    default   => 'var(--ds)',
                                };
                                $zoneFg = match ($zone['color'] ?? 'danger') {
                                    'primary' => '#fff',
                                    'success' => 'var(--bt)',
                                    'info'    => '#fff',
                                    'warning' => 'var(--wt)',
                                    default   => 'var(--dt)',
                                };
                            @endphp
                            <div class="list-item">
                                <div style="width:24px;height:24px;border-radius:var(--r-sm);background:var(--surface);color:var(--on-surface-muted);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0">{{ $idx + 1 }}</div>
                                <div class="list-avatar">{{ $initials }}</div>
                                <div class="list-info">
                                    <a href="{{ route('students.show', $student) }}" class="list-name" style="color:var(--on-surface);display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                        {{ $student->full_name }}
                                    </a>
                                    <div class="list-meta">{{ $student->currentEnrollment?->schoolClass?->name ?? '-' }}</div>
                                </div>
                                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
                                    <span class="list-score {{ $bal <= 1499 ? 'danger' : 'warning' }}">{{ number_format($bal) }} poin</span>
                                    <span class="badge" style="background:{{ $zoneBg }};color:{{ $zoneFg }}">{{ $zone['name'] }}</span>
                                </div>
                            </div>
                        @empty
                            <div style="padding:28px 0;text-align:center">
                                <div class="text-sm" style="font-weight:600;color:var(--on-surface);margin-bottom:4px">Kondisi Terkendali</div>
                                <p class="text-muted text-sm" style="margin:0">Tidak ada siswa aktif di zona Intervensi Khusus.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Tier 3: Analitik Tren & Sebaran Populasi --}}
        <div class="section">
            <div class="grid grid-2">
                {{-- Kiri: Tren 30 Hari --}}
                <div class="card no-hover" style="overflow:hidden;display:flex;flex-direction:column">
                    <div style="display:flex;align-items:baseline;justify-content:space-between;gap:var(--sp-sm);margin-bottom:var(--sp-md)">
                        <h3 style="font-size:1rem;font-weight:600;margin:0">Tren Kasus 30 Hari</h3>
                        <span class="text-sm text-muted" style="font-size:.75rem">Kasus Baru vs Selesai</span>
                    </div>
                    <div style="height:236px;position:relative;width:100%;min-width:0;flex:1">
                        <canvas id="trendChart"
                            data-labels="{{ json_encode($trendLabels) }}"
                            data-new="{{ json_encode($trendNew) }}"
                            data-done="{{ json_encode($trendDone) }}"></canvas>
                    </div>
                </div>

                {{-- Kanan: Distribusi Zona Poin --}}
                <div class="card no-hover">
                    <div style="display:flex;align-items:baseline;justify-content:space-between;gap:var(--sp-sm);margin-bottom:var(--sp-md)">
                        <h3 style="font-size:1rem;font-weight:600;margin:0">Distribusi Zona Poin</h3>
                        <span class="text-sm text-muted" style="font-size:.75rem">Sebaran siswa aktif</span>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:8px">
                        @foreach($zoneCounts as $name => $zone)
                            @php
                                $count = $zone['count'];
                                $pct = $maxZoneCount > 0 ? ($count / $maxZoneCount) * 100 : 0;
                                $bg = match ($zone['color'] ?? 'success') {
                                    'danger'  => 'var(--danger)',
                                    'warning' => 'var(--accent)',
                                    'info'    => '#3b82f6',
                                    'primary' => 'var(--primary)',
                                    default   => 'var(--tertiary)',
                                };
                            @endphp
                            <a href="{{ route('monitor-poin.index', ['zone' => $name]) }}" style="display:flex;align-items:center;gap:var(--sp-sm);padding:4px 6px;border-radius:var(--r-sm);text-decoration:none;color:inherit;transition:background .15s" onmouseover="this.style.background='var(--surface)'" onmouseout="this.style.background='transparent'" title="Filter zona {{ $name }} ({{ $zone['range'] }})">
                                <div style="width:136px;min-width:136px">
                                    <div style="font-size:.75rem;font-weight:600;color:var(--on-surface);line-height:1.2">{{ $name }}</div>
                                    <div class="mono" style="font-size:.6875rem;color:var(--on-surface-muted);line-height:1.2">{{ $zone['range'] }}</div>
                                </div>
                                <div style="flex:1;height:10px;background:var(--surface);border-radius:var(--r-full);overflow:hidden">
                                    @if($count > 0)
                                        <div style="height:100%;width:{{ max($pct, 4) }}%;background:{{ $bg }};border-radius:var(--r-full)"></div>
                                    @endif
                                </div>
                                <div class="mono" style="min-width:32px;font-size:.75rem;font-weight:700;text-align:right;color:{{ $count > 0 ? 'var(--on-surface)' : 'var(--on-surface-muted)' }}">{{ $count }}</div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" integrity="sha384-e6nUZLBkQ86NJ6TVVKAeSaK8jWa3NhkYWZFomE39AvDbQWeie9PlQqM3pmYW5d1g" crossorigin="anonymous"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) lucide.createIcons();

        const canvas = document.getElementById('trendChart');
        if (canvas && typeof Chart !== 'undefined') {
            const styles = getComputedStyle(document.documentElement);
            const mutedColor = styles.getPropertyValue('--on-surface-muted').trim() || '#64748b';
            const borderColor = styles.getPropertyValue('--border').trim() || 'rgba(148,163,184,0.2)';

            const labels = JSON.parse(canvas.dataset.labels || '[]');
            const newData = JSON.parse(canvas.dataset.new || '[]');
            const doneData = JSON.parse(canvas.dataset.done || '[]');

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Kasus Baru', data: newData, backgroundColor: 'rgba(245,158,11,0.75)', borderColor: '#f59e0b', borderWidth: 1, borderRadius: 4 },
                        { label: 'Selesai', data: doneData, backgroundColor: 'rgba(5,150,105,0.75)', borderColor: '#059669', borderWidth: 1, borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: { color: mutedColor, font: { size: 11 }, boxWidth: 10, padding: 12 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: mutedColor, stepSize: 1, font: { size: 10 } },
                            grid: { color: borderColor }
                        },
                        x: {
                            ticks: { color: mutedColor, font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 },
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    });
    </script>
</x-app-layout>
