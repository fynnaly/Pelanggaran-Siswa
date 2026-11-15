<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Laporan Pelanggaran per Kelas</h1>
                <p class="text-muted text-sm">Semua kelas tampil, termasuk kelas tanpa kasus (bar kosong). Dihitung dari jumlah kasus, bukan poin.</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap" class="no-print">
                <a href="{{ route('laporan.kelas.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
                <a href="{{ route('laporan.per-kelas.export', request()->query()) }}" class="btn btn-secondary btn-sm"><i data-lucide="download" class="icon-sm"></i> Export XLSX</a>
                <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><i data-lucide="printer" class="icon-sm"></i> Cetak / Simpan PDF</button>
            </div>
        </div>
    </x-slot>

    @php
        $worstKasus = $rows->max('kasus');
        $worstRow = $worstKasus > 0 ? $rows->firstWhere('kasus', $worstKasus) : null;
        $totalKasus = $rows->sum('kasus');
        $totalPrestasi = $rows->sum('prestasi');
        // Keterangan periode aktif agar jelas data dihitung mulai/dari mana
        $periodLabel = ($filters['year'] ? 'Tahun Ajaran '.$years->firstWhere('id', $filters['year'])?->name : 'Semua Tahun Ajaran')
            .(($filters['from'] || $filters['to']) ? ' · '.($filters['from'] ? \Carbon\Carbon::parse($filters['from'])->translatedFormat('d M Y') : 'awal').' s/d '.($filters['to'] ? \Carbon\Carbon::parse($filters['to'])->translatedFormat('d M Y') : 'sekarang') : '');
    @endphp

    {{-- Header khusus cetak --}}
    <div class="main-wrap">
        <div class="print-only" style="display:none;margin-bottom:16px">
            <div style="text-align:center;border-bottom:2px solid #000;padding-bottom:8px">
                <h2 style="font-size:16px;font-weight:800;margin:0;text-transform:uppercase">LAPORAN PELANGGARAN PER KELAS</h2>
                <div style="font-size:12px;font-weight:700">{{ \App\Models\Setting::get('school_name', 'SMK Baktinusantara 666') }}</div>
                <div style="font-size:11px;color:#475569">
                    {{ $filters['year'] ? 'Tahun Ajaran: '.$years->firstWhere('id', $filters['year'])?->name : 'Semua Tahun Ajaran' }}
                    @if($filters['from'] || $filters['to'])
                        &middot; Periode {{ $filters['from'] ?: 'awal' }} s/d {{ $filters['to'] ?: 'sekarang' }}
                    @endif
                    &middot; Dicetak: {{ now()->translatedFormat('d F Y') }}
                </div>
            </div>
        </div>

        <div class="toolbar no-print">
            <form method="GET" action="{{ route('laporan.per-kelas') }}" style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;align-items:center;width:100%">
                <select name="year" class="input" style="width:auto;min-width:150px" onchange="this.form.submit()">
                    <option value="">Semua Tahun Ajaran</option>
                    @foreach($years as $y)
                        <option value="{{ $y->id }}" {{ (string) $filters['year'] === (string) $y->id ? 'selected' : '' }}>{{ $y->name }}</option>
                    @endforeach
                </select>
                <span class="text-muted text-sm" style="white-space:nowrap">Tanggal <strong>mulai</strong></span>
                <input type="date" name="from" class="input" style="width:auto" value="{{ $filters['from'] }}" title="Tanggal mulai (kasus pada tanggal ini ikut dihitung)" onchange="this.form.submit()">
                <span class="text-muted text-sm" style="white-space:nowrap">s/d <strong>selesai</strong></span>
                <input type="date" name="to" class="input" style="width:auto" value="{{ $filters['to'] }}" title="Tanggal selesai (kasus pada tanggal ini ikut dihitung)" onchange="this.form.submit()">
                <a href="{{ route('laporan.per-kelas') }}" class="btn btn-secondary btn-sm">Reset</a>
            </form>
        </div>

        <p class="text-sm text-muted" style="margin:0 0 var(--sp-md);display:flex;align-items:center;gap:6px">
            <i data-lucide="calendar" class="icon-sm"></i> Periode: <strong style="color:var(--on-surface)">{{ $periodLabel }}</strong> · Kasus dihitung dari tanggal kejadian (fallback: tanggal dicatat)
        </p>

        <div style="display:flex;gap:var(--sp-md);flex-wrap:wrap;margin-bottom:var(--sp-md)">
            <div class="card" style="flex:1;min-width:200px;margin:0">
                <div class="text-sm text-muted">Total Kasus Pelanggaran</div>
                <div class="mono" style="font-size:1.5rem;font-weight:700">{{ number_format($totalKasus) }}</div>
            </div>
            <div class="card" style="flex:1;min-width:200px;margin:0">
                <div class="text-sm text-muted">Total Prestasi</div>
                <div class="mono" style="font-size:1.5rem;font-weight:700;color:var(--success)">{{ number_format($totalPrestasi) }}</div>
            </div>
            <div class="card" style="flex:2;min-width:280px;margin:0">
                <div class="text-sm text-muted">Kelas Paling Sering Melanggar</div>
                @if($worstRow)
                    <div style="font-size:1.125rem;font-weight:700;color:var(--danger)">{{ $worstRow['class']->name }}
                        <span class="text-sm text-muted" style="font-weight:400">— {{ $worstKasus }} kasus</span>
                    </div>
                @else
                    <div style="font-size:1.125rem;font-weight:700;color:var(--success)">Tidak ada kasus tercatat</div>
                @endif
            </div>
        </div>

        <div class="card print-inline break-avoid">
            <h2 style="font-weight:600;margin:0 0 var(--sp-sm)">Perbandingan Kasus Antar Kelas</h2>
            @if($rows->isEmpty())
                <p class="text-muted text-sm" style="padding:var(--sp-lg) 0;text-align:center">Belum ada kelas pada filter ini.</p>
            @else
                <div style="position:relative;height:{{ max(240, $rows->count() * 42) }}px">
                    <canvas id="kelasChart"
                        data-labels='@json($rows->map(fn ($r) => $r["class"]->name)->values())'
                        data-values='@json($rows->pluck("kasus")->map(fn ($v) => (int) $v)->values())'
                        data-max-index='@json($worstRow ? $rows->search(fn ($r) => $r === $worstRow) : -1)'></canvas>
                </div>
                <p class="text-muted text-sm" style="margin:var(--sp-sm) 0 0">Bar merah = kelas paling sering melanggar. Bar kosong = kelas tanpa kasus pada filter ini.</p>
            @endif
        </div>

        <table class="report-box">
            <thead><tr><td>
                <div class="report-box-head"><h2 style="font-weight:600;margin:0">Tabel Ringkasan</h2></div>
            </td></tr></thead>
            <tbody><tr><td class="report-box-cell">
                <div class="table-wrap">
                    <table>
                        <thead><tr>
                            <th style="width:40px">No</th>
                            <th>Kelas</th>
                            <th>Tahun Ajaran</th>
                            <th>Wali Kelas</th>
                            <th style="text-align:right">Jumlah Siswa</th>
                            <th style="text-align:right">Kasus Pelanggaran</th>
                            <th style="text-align:right">Prestasi</th>
                            <th class="no-print" style="width:110px">Aksi</th>
                        </tr></thead>
                        <tbody>
                            @forelse($rows as $i => $row)
                                @php $isWorst = $worstRow !== null && $row === $worstRow; @endphp
                                <tr>
                                    <td class="text-muted">{{ $i + 1 }}</td>
                                    <td style="font-weight:600;white-space:nowrap">
                                        {{ $row['class']->name }}
                                        @if($isWorst)<span class="badge badge-danger" style="margin-left:6px">Paling sering melanggar</span>@endif
                                    </td>
                                    <td class="text-sm text-muted">{{ $row['class']->academicYear?->name ?? '-' }}</td>
                                    <td class="text-sm">{{ $row['class']->homeroomTeacher?->name ?? '-' }}</td>
                                    <td class="mono" style="text-align:right">{{ $row['students'] }}</td>
                                    <td class="mono" style="text-align:right;font-weight:700;{{ $row['kasus'] > 0 ? 'color:var(--danger)' : '' }}">{{ $row['kasus'] }}</td>
                                    <td class="mono" style="text-align:right;color:var(--success)">{{ $row['prestasi'] }}</td>
                                    <td class="no-print">
                                        <a href="{{ route('laporan.kelas', array_merge(['schoolClass' => $row['class']->id], $linkParams)) }}" class="action-btn" title="Buka riwayat lengkap kelas ini"><i data-lucide="file-text" style="width:14px;height:14px"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-muted" style="text-align:center;padding:var(--sp-lg)">Belum ada kelas pada filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </td></tr></tbody>
        </table>

        <div class="print-only" style="display:none;margin-top:24px">
            <div style="display:flex;justify-content:space-between;text-align:center;font-size:11px">
                <div style="width:35%">
                    <div>Mengetahui,</div>
                    <div style="font-weight:700">{{ \App\Models\Setting::get('signer_kesiswaan_label', 'Wakasek Kesiswaan') }}</div>
                    <div style="height:50px"></div>
                    <div style="font-weight:700;border-top:1px solid #000;padding-top:2px;display:inline-block;min-width:160px">
                        {{ \App\Models\Setting::get('signer_kesiswaan_nama') ?: '____________________' }}
                    </div>
                </div>
                <div style="width:35%">
                    <div>{{ now()->translatedFormat('d F Y') }}</div>
                    <div style="font-weight:700">{{ \App\Models\Setting::get('signer_kepala_label', 'Kepala Sekolah') }}</div>
                    <div style="height:50px"></div>
                    <div style="font-weight:700;border-top:1px solid #000;padding-top:2px;display:inline-block;min-width:160px">
                        {{ \App\Models\Setting::get('signer_kepala_nama') ?: '____________________' }}
                    </div>
                    @if(\App\Models\Setting::get('signer_kepala_nip'))
                        <div>NIP. {{ \App\Models\Setting::get('signer_kepala_nip') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Kontainer table-based: kepala di <thead> sehingga berulang otomatis saat terpotong halaman */
        .report-box {
            width:100%; background:#ffffff; border:1px solid #e5e7eb; border-radius:10px;
            border-collapse:separate; border-spacing:0; margin-bottom:var(--sp-lg);
        }
        .report-box > thead > tr > td, .report-box > tbody > tr > td {
            padding:0; border:none; background:transparent;
        }
        .report-box-head {
            padding:var(--sp-sm) var(--sp-md); background:#f3f4f6; border-bottom:1px solid #e5e7eb;
        }
        .report-box-cell { padding:var(--sp-md); }

        @media print {
            .no-print, .app-header, aside, .sidebar, .sidebar-mobile, .sidebar-overlay, .mobile-topbar { display: none !important; }
            .print-only { display: block !important; }
            .print-inline { border: 1px solid #9ca3af !important; border-radius: 4px !important; box-shadow: none !important; }
            .break-avoid { page-break-inside: avoid; }
            .report-box { border: 1px solid #9ca3af !important; border-radius: 6px !important; box-shadow: none !important; }
            .report-box > thead > tr > td, .report-box > tbody > tr > td { padding: 0 !important; border: none !important; }
            .report-box-head {
                background: #f3f4f6 !important; border-bottom: 1px solid #9ca3af !important;
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
            .app-shell { margin-left: 0 !important; }
            .main-wrap { padding: 0 !important; max-width: none !important; }
            table { font-size: 9px !important; }
            th, td { padding: 3px 5px !important; }
            thead { display: table-header-group; }
            /* Baris isi yang dilindungi dari terpotong, BUKAN baris kontainer */
            .report-box-cell tr { page-break-inside: avoid; }
            @page { size: A4 landscape; margin: 10mm; }
        }
    </style>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" integrity="sha384-e6nUZLBkQ86NJ6TVVKAeSaK8jWa3NhkYWZFomE39AvDbQWeie9PlQqM3pmYW5d1g" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            lucide.createIcons();
            const el = document.getElementById('kelasChart');
            if (!el) return;
            const dark = document.documentElement.classList.contains('dark');
            const labels = JSON.parse(el.dataset.labels || '[]');
            const values = JSON.parse(el.dataset.values || '[]');
            const maxIndex = parseInt(el.dataset.maxIndex || '-1', 10);

            // Bar kelas paling sering melanggar diberi warna merah, sisanya biru.
            const colors = values.map((v, i) => (i === maxIndex && v > 0) ? '#ef4444' : '#465fff');

            new Chart(el, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{ label: 'Kasus', data: values, backgroundColor: colors, borderRadius: 6 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: dark ? '#d1d5db' : '#374151', maxRotation: 45, minRotation: 0, font: { family: 'Outfit', size: 11 } } },
                        y: { beginAtZero: true, ticks: { precision: 0, color: dark ? '#9ca3af' : '#6b7280' }, grid: { color: dark ? '#1f2937' : '#f3f4f6' } }
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
