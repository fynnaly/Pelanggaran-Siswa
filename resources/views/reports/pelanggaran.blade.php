<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Statistik Pelanggaran</h1>
                <p class="text-muted text-sm">Dihitung dari jumlah kasus, bukan poin. Kasus yang dibuang tidak dihitung.</p>
            </div>
        </div>
    </x-slot>

    @php
        $badge = ['ringan' => 'badge-info', 'sedang' => 'badge-warning', 'berat' => 'badge-danger', 'sangat_berat' => 'badge-danger'];
        $top = $categories->take(10);
    @endphp

    <div class="main-wrap">
        <div class="toolbar">
            <form method="GET" action="{{ route('laporan.pelanggaran') }}" style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;align-items:center;width:100%">
                <select name="year" class="input" style="width:auto;min-width:150px" onchange="this.form.submit()">
                    <option value="">Semua Tahun Ajaran</option>
                    @foreach($years as $y)
                        <option value="{{ $y->id }}" {{ (string) $filters['year'] === (string) $y->id ? 'selected' : '' }}>{{ $y->name }}</option>
                    @endforeach
                </select>
                <select name="class" class="input" style="width:auto;min-width:150px" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ (string) $filters['class'] === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                <span class="text-muted text-sm" style="white-space:nowrap">Tanggal <strong>mulai</strong></span>
                <input type="date" name="from" class="input" style="width:auto" value="{{ $filters['from'] }}" title="Tanggal mulai (start) — kasus pada tanggal ini ikut dihitung">
                <span class="text-muted text-sm" style="white-space:nowrap">s/d <strong>selesai</strong></span>
                <input type="date" name="to" class="input" style="width:auto" value="{{ $filters['to'] }}" title="Tanggal selesai (end) — kasus pada tanggal ini ikut dihitung">
                <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter" class="icon-sm"></i> Terapkan</button>
                <a href="{{ route('laporan.pelanggaran') }}" class="btn btn-secondary btn-sm">Reset</a>
            </form>
        </div>

        <div class="card" x-data="{ showAll: false }">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-md);gap:var(--sp-sm);flex-wrap:wrap">
                <div>
                    <h2 style="font-weight:600;margin:0" x-text="showAll ? 'Semua Pelanggaran yang Pernah Terjadi ({{ $categories->count() }})' : '10 Pelanggaran Terbanyak'">10 Pelanggaran Terbanyak</h2>
                    <p class="text-muted text-sm" style="margin:2px 0 0">Klik bar pada diagram untuk melihat daftar siswa yang pernah melanggar.</p>
                </div>
                @if($categories->count() > 10)
                    <button type="button" @click="showAll = !showAll; $nextTick(() => window.toggleChartScope && window.toggleChartScope(showAll))" class="btn btn-secondary btn-sm">
                        <span x-text="showAll ? 'Tampilkan Top 10' : 'Tampilkan Semua (' + {{ $categories->count() }} + ')'">Tampilkan Semua ({{ $categories->count() }})</span>
                    </button>
                @endif
            </div>
            @if($categories->isEmpty())
                <p class="text-muted text-sm" style="padding:var(--sp-lg) 0;text-align:center">Belum ada data pada filter ini.</p>
            @else
                <div id="chartContainer" style="position:relative;height:{{ max(180, min(10, $categories->count()) * 36) }}px">
                    <canvas id="catChart"
                        data-labels='@json($top->pluck('name'))'
                        data-values='@json($top->pluck('total')->map(fn ($v) => (int) $v))'
                        data-urls='@json($top->map(fn ($c) => route('laporan.pelanggaran.kategori', array_merge(['violationCategory' => $c->id], $linkParams)))->values())'
                        data-all-labels='@json($categories->pluck('name'))'
                        data-all-values='@json($categories->pluck('total')->map(fn ($v) => (int) $v))'
                        data-all-urls='@json($categories->map(fn ($c) => route('laporan.pelanggaran.kategori', array_merge(['violationCategory' => $c->id], $linkParams)))->values())'></canvas>
                </div>
            @endif
        </div>

        <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-sm)">
                <h2 style="font-weight:600;margin:0">Daftar Seluruh Pelanggaran</h2>
                <span class="text-muted text-sm">{{ $categories->count() }} jenis pelanggaran tercatat</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th style="width:40px">#</th>
                        <th>Pelanggaran</th>
                        <th>Tingkat</th>
                        <th style="text-align:right">Kasus</th>
                        <th style="text-align:right">Siswa</th>
                    </tr></thead>
                    <tbody>
                        @forelse($categories as $i => $c)
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <a href="{{ route('laporan.pelanggaran.kategori', array_merge(['violationCategory' => $c->id], $linkParams)) }}" style="font-weight:600;text-decoration:none;color:inherit">
                                        <span class="mono text-muted">{{ $c->code }}</span> {{ $c->name }}
                                    </a>
                                    @if($c->parent_name)<div class="text-muted text-sm">{{ $c->parent_name }}</div>@endif
                                </td>
                                <td><span class="badge {{ $badge[$c->severity] ?? 'badge-info' }}">{{ str_replace('_', ' ', $c->severity ?? '-') }}</span></td>
                                <td class="mono" style="text-align:right;font-weight:700">{{ $c->total }}</td>
                                <td class="mono" style="text-align:right">{{ $c->students }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted" style="text-align:center;padding:var(--sp-lg)">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h2 style="font-weight:600;margin-bottom:var(--sp-sm)">20 Siswa Paling Sering Melanggar</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th style="width:40px">#</th>
                        <th>Siswa</th>
                        <th>Kelas</th>
                        <th>Terakhir</th>
                        <th style="text-align:right">Kasus</th>
                    </tr></thead>
                    <tbody>
                        @forelse($students as $i => $s)
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <a href="{{ route('students.show', $s->id) }}" style="font-weight:600;text-decoration:none;color:inherit">{{ $s->full_name }}</a>
                                    <div class="text-muted text-sm">NISN: {{ $s->nisn ?? '-' }}</div>
                                </td>
                                <td>{{ $s->class_name ?? 'Tanpa Kelas' }}</td>
                                <td>{{ \Carbon\Carbon::parse($s->last_date)->format('d/m/Y') }}</td>
                                <td class="mono" style="text-align:right;font-weight:700;color:#ef4444">{{ $s->total }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted" style="text-align:center;padding:var(--sp-lg)">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" integrity="sha384-e6nUZLBkQ86NJ6TVVKAeSaK8jWa3NhkYWZFomE39AvDbQWeie9PlQqM3pmYW5d1g" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            lucide.createIcons();
            const el = document.getElementById('catChart');
            const container = document.getElementById('chartContainer');
            if (!el) return;
            const dark = document.documentElement.classList.contains('dark');

            const topLabels = JSON.parse(el.dataset.labels || '[]');
            const topValues = JSON.parse(el.dataset.values || '[]');
            let currentUrls = JSON.parse(el.dataset.urls || '[]');

            const allLabels = JSON.parse(el.dataset.allLabels || '[]');
            const allValues = JSON.parse(el.dataset.allValues || '[]');
            const allUrls = JSON.parse(el.dataset.allUrls || '[]');

            const chart = new Chart(el, {
                type: 'bar',
                data: {
                    labels: topLabels,
                    datasets: [{ label: 'Kasus', data: topValues, backgroundColor: '#465fff', borderRadius: 6 }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0, color: dark ? '#9ca3af' : '#6b7280' }, grid: { color: dark ? '#1f2937' : '#f3f4f6' } },
                        y: { grid: { display: false }, ticks: { color: dark ? '#d1d5db' : '#374151', font: { family: 'Outfit', size: 12 } } }
                    },
                    onHover: function (e) {
                        const points = this.getElementsAtEventForMode(e.native || e, 'nearest', { intersect: true }, false);
                        this.canvas.style.cursor = points.length ? 'pointer' : 'default';
                    },
                    onClick: function (e) {
                        const nativeEvent = e.native || e;
                        const points = this.getElementsAtEventForMode(nativeEvent, 'nearest', { intersect: true }, false);
                        if (points.length) {
                            const idx = points[0].index;
                            if (currentUrls[idx]) {
                                window.location.href = currentUrls[idx];
                            }
                        }
                    }
                }
            });

            // Native click listener fallback
            el.addEventListener('click', function (evt) {
                const points = chart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, false);
                if (points.length) {
                    const idx = points[0].index;
                    if (currentUrls[idx]) {
                        window.location.href = currentUrls[idx];
                    }
                }
            });

            window.toggleChartScope = function (showAll) {
                const labels = showAll ? allLabels : topLabels;
                const values = showAll ? allValues : topValues;
                currentUrls = showAll ? allUrls : JSON.parse(el.dataset.urls || '[]');

                if (container) {
                    const count = labels.length;
                    container.style.height = Math.max(180, count * 34) + 'px';
                }

                chart.data.labels = labels;
                chart.data.datasets[0].data = values;
                chart.resize();
                chart.update();
            };
        });
    </script>
    @endpush
</x-app-layout>
