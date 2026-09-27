<x-app-layout>
    <x-slot name="header">

        <div class="page-header">
            <div>
                <h1>Dashboard</h1>
                <p class="text-muted text-sm">Ringkasan data pelanggaran dan pencapaian siswa</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('kasus-pelanggaran.create') }}" class="btn btn-primary"><i data-lucide="plus" class="icon-sm"></i> Buat Kasus</a>
                <a href="{{ route('students.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="search" class="icon-sm"></i> Cari Siswa</a>
                <a href="{{ route('students.export') }}" class="btn btn-secondary btn-sm"><i data-lucide="download" class="icon-sm"></i> Export</a>
            </div>
        </div>
    </x-slot>

    <div class="main-wrap">
        <div class="grid grid-4">
            <div class="card-glass">
                <div style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="users" class="icon-lg" style="color:var(--tertiary)"></i><div class="stat-label">Siswa Aktif</div></div>
                <div class="stat-value">{{ number_format($totalStudentsActive) }}</div>
                <div class="stat-change positive">{{ $activeAcademicYear?->name ?? 'Tahun ajaran aktif' }}</div>
            </div>
            <div class="card-glass">
                <div style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="alert-triangle" class="icon-lg" style="color:var(--accent)"></i><div class="stat-label">Ditemukan</div></div>
                <div class="stat-value" style="color:var(--accent)">{{ number_format($totalCasesFound) }}</div>
                <div class="stat-change negative">Perlu diverifikasi</div>
            </div>
            <div class="card-glass">
                <div style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="check-circle" class="icon-lg" style="color:var(--tertiary)"></i><div class="stat-label">Selesai</div></div>
                <div class="stat-value">{{ number_format($totalCasesDone) }}</div>
                @php $rate = $totalCases > 0 ? round(($totalCasesDone/$totalCases)*100) : 0; @endphp
                <div class="stat-change positive">{{ $rate }}% resolve rate</div>
            </div>
            <div class="card-glass">
                <div style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="tags" class="icon-lg" style="color:var(--primary)"></i><div class="stat-label">Kategori Aktif</div></div>
                <div class="stat-value">{{ $totalCategoriesActive }}/{{ $totalCategories }}</div>
                <div class="stat-change positive">Kategori pelanggaran</div>
            </div>
        </div>

        @if($totalCases > 0)
        <div class="section">
            <div class="card no-hover">
                <h3 style="font-size:0.875rem;font-weight:600;margin-bottom:var(--sp-md)">Progres Penanganan Kasus</h3>
                <div style="display:flex;align-items:center;gap:var(--sp-md);margin-bottom:var(--sp-sm);flex-wrap:wrap">
                    @php
                        $total = max($totalCases, 1);
                        $segs = [
                            ['label' => 'Ditemukan', 'count' => $totalCasesFound, 'bg' => 'var(--accent)'],
                            ['label' => 'Divalidasi', 'count' => $totalCasesValidated, 'bg' => 'var(--primary)'],
                            ['label' => 'Selesai', 'count' => $totalCasesDone, 'bg' => 'var(--tertiary)'],
                            ['label' => 'Dibuang', 'count' => $totalCasesDismissed, 'bg' => 'var(--on-surface-muted)'],
                        ];
                    @endphp
                    <div style="flex:1;min-width:200px;height:16px;background:var(--bs);border-radius:var(--r-sm);overflow:hidden;display:flex">
                        @foreach($segs as $s)
                            @if($s['count'] > 0)
                                <div style="width:{{ ($s['count']/$total)*100 }}%;height:100%;background:{{ $s['bg'] }}" title="{{ $s['label'] }}: {{ $s['count'] }}"></div>
                            @endif
                        @endforeach
                    </div>
                    <div style="font-size:.75rem;color:var(--on-surface-muted);font-family:ui-monospace,monospace">{{ $totalCasesFound+$totalCasesValidated+$totalCasesDone+$totalCasesDismissed }} total</div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:var(--on-surface-muted);flex-wrap:wrap;gap:var(--sp-sm)">
                    @foreach($segs as $s)
                        <span>{{ $s['label'] }}: {{ $s['count'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <div class="section">
            <div class="layout-2col">
                <div>
                    <div class="section-header">
                        <h2 style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="file-text" class="icon"></i> Kasus Terbaru</h2>
                        <a href="{{ route('kasus-pelanggaran.index') }}" class="btn btn-secondary btn-sm">Lihat Semua</a>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>No. Kasus</th><th>Siswa</th><th>Kategori</th><th>Poin</th><th>Status</th><th>Tanggal</th></tr></thead>
                            <tbody>
                                @forelse($recentCases as $case)
                                    <tr>
                                        <td class="mono">{{ $case->case_number }}</td>
                                        <td><strong>{{ $case->student?->full_name ?? '-' }}</strong><br><span class="text-muted text-sm">{{ $case->student?->currentEnrollment?->schoolClass?->name ?? '' }}</span></td>
                                        <td>{{ $case->violationCategory?->name ?? '-' }}</td>
                                        <td class="mono">-{{ $case->violationCategory?->points ?? 0 }}</td>
                                        <td>
                                            @if($case->status==='found')<span class="badge badge-warning">Diproses</span>
                                            @elseif($case->status==='validated')<span class="badge" style="background:var(--ws);color:var(--wt)">Divalidasi</span>
                                            @elseif($case->status==='done')<span class="badge badge-success">Selesai</span>
                                            @else<span class="badge badge-danger">{{ ucfirst($case->status) }}</span>@endif
                                        </td>
                                        <td class="text-sm text-muted">{{ $case->created_at?->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada kasus.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <div class="section-header">
                                            <h2 style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="users" class="icon"></i> 5 Siswa Poin Terendah</h2>
                                        </div>
                                        <div class="card">
                                            @forelse($lowPointStudents as $idx => $student)
                                                @php
                                                    $initials = collect(explode(' ',$student->full_name))->map(fn($w)=>strtoupper(substr($w,0,1)))->take(2)->implode('');
                                                    $bal = (int) ($student->latest_balance ?? 0);
                                                                                    $zone = ['name' => 'Intervensi Khusus', 'color' => 'danger'];
                                                    foreach (config('zones.zones', []) as $_z) {
                                                        $minOk = $_z['min'] === null || $bal >= $_z['min'];
                                                        $maxOk = $_z['max'] === null || $bal <= $_z['max'];
                                                        if ($minOk && $maxOk) { $zone = $_z; break; }
                                                    }
                                                    $zoneBg = match ($zone['color'] ?? 'danger') {
                                                        'primary' => 'var(--primary)',
                                                        'success' => 'var(--tertiary)',
                                                        'info'    => 'var(--primary)',
                                                        'warning' => 'var(--ws)',
                                                        default   => 'var(--ds)',
                                                    };
                                                    $zoneFg = match ($zone['color'] ?? 'danger') {
                                                        'primary' => '#fff',
                                                        'success' => 'var(--bt)',
                                                        'info'    => 'var(--primary)',
                                                        'warning' => 'var(--wt)',
                                                        default   => 'var(--dt)',
                                                    };
                                                @endphp
                                                <div class="list-item">
                                                    <div style="width:24px;height:24px;border-radius:var(--r-sm);background:var(--surface);color:var(--on-surface-muted);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0">{{ $idx+1 }}</div>
                                                    <div class="list-avatar">{{ $initials }}</div>
                                                    <div class="list-info">
                                                        <div class="list-name">{{ $student->full_name }}</div>
                                                        <div class="list-meta">{{ $student->currentEnrollment?->schoolClass?->name ?? '-' }}</div>
                                                    </div>
                                                    <div style="display:flex;align-items:center;gap:var(--sp-sm)">
                                                        <span class="list-score {{ $bal<=1499?'danger':'warning' }}">{{ $bal }}</span>
                                                        <span class="badge" style="background:{{ $zoneBg }};color:{{ $zoneFg }}">{{ $zone['name'] }}</span>
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-muted text-sm" style="padding:var(--sp-md) 0"><i data-lucide="check-circle" class="icon-sm"></i> Tidak ada siswa dengan poin kritis.</p>
                                            @endforelse
                                        </div>
                </div>
            </div>
        </div>
        </div>

{{-- Distribusi Zona Poin --}}
        <div class="section">
            <div class="card no-hover">
                <h3 style="font-size:0.875rem;font-weight:600;margin-bottom:var(--sp-md)">Distribusi Zona Poin</h3>
                @foreach($zoneCounts as $name => $zone)
                                    @php
                                        $count = $zone['count'];
                                        $pct = $maxZoneCount > 0 ? ($count / $maxZoneCount) * 100 : 0;
                                        $bg = $zone['color'] === 'danger' ? 'var(--ds)' : ($zone['color'] === 'warning' ? 'var(--ws)' : ($zone['color'] === 'info' ? '#dbeafe' : ($zone['color'] === 'primary' ? 'var(--primary)' : 'var(--bs)')));
                                    @endphp
                                    <div style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-sm)" title="{{ $name }} ({{ $zone['range'] }})">
                                        <div style="width:120px;min-width:120px;font-size:.75rem;font-weight:600;color:var(--on-surface)">{{ $name }}</div>
                                        <div style="flex:1;height:12px;background:var(--surface);border-radius:var(--r-full);overflow:hidden">
                                            <div style="height:100%;width:{{ $pct }}%;background:{{ $bg }};border-radius:var(--r-full);min-width:8px"></div>
                                        </div>
                                        <div style="min-width:36px;font-size:.75rem;font-weight:700;text-align:right">{{ $count }}</div>
                                    </div>
                                @endforeach
            </div>
        </div>
        {{-- Tren 30 Hari --}}
        <div class="section">
            <div class="card no-hover">
                <h3 style="font-size:0.875rem;font-weight:600;margin-bottom:var(--sp-md)">Tren 30 Hari</h3>
                <div style="height:200px;position:relative">
                    <canvas id="trendChart" 
                        data-labels="{{ json_encode($trendLabels) }}"
                        data-new="{{ json_encode($trendNew) }}"
                        data-done="{{ json_encode($trendDone) }}"></canvas>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" onload="window.__chartLoaded=true"></script>
    <script>
    document.addEventListener('DOMContentLoaded',()=>{
        lucide.createIcons();
        // Progres Penanganan — horizontal stacked bar
        var pc = document.getElementById('progressChart');
        if (pc && window.__chartLoaded) {
            var d = pc.dataset;
            new Chart(pc, {
                type: 'bar',
                data: {
                    labels: ['Status Kasus'],
                    datasets: [
                        { label: 'Ditemukan', data: [+d.found], backgroundColor: '#f59e0b', borderRadius: 6, barThickness: 16 },
                        { label: 'Divalidasi', data: [+d.validated], backgroundColor: '#1e3a5f', borderRadius: 6, barThickness: 16 },
                        { label: 'Selesai', data: [+d.done], backgroundColor: '#059669', borderRadius: 6, barThickness: 16 },
                        { label: 'Dibuang', data: [+d.dismissed], backgroundColor: '#94a3b8', borderRadius: 6, barThickness: 16 }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.x } } },
                    scales: { x: { stacked: true, display: false }, y: { stacked: true, display: false } }
                }
            });
        }
        // Trend chart
        var canvas = document.getElementById('trendChart');
        if (canvas && window.__chartLoaded) {
            var labels = JSON.parse(canvas.dataset.labels || '[]');
            var newData = JSON.parse(canvas.dataset.new || '[]');
            var doneData = JSON.parse(canvas.dataset.done || '[]');
            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Baru', data: newData, backgroundColor: 'rgba(245,158,11,0.7)', borderColor: '#f59e0b', borderWidth: 1, borderRadius: 4 },
                        { label: 'Selesai', data: doneData, backgroundColor: 'rgba(5,150,105,0.7)', borderColor: '#059669', borderWidth: 1, borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12, padding: 12 } } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 } }, grid: { color: 'rgba(0,0,0,0.06)' } },
                        x: { ticks: { font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 }, grid: { display: false } }
                    }
                }
            });
        }
    });
    </script>
</x-app-layout>
