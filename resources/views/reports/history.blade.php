<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>{{ $reportTitle }}</h1>
                <p class="text-muted text-sm">
                    @if($student)
                        {{ $student->full_name }} · NIS {{ $student->nis }} · Kelas {{ $student->currentEnrollment?->schoolClass?->name ?? '-' }}
                    @else
                        {{ $schoolClass->name }} · {{ $schoolClass->academicYear?->name ?? 'Tahun ajaran tidak tersedia' }}
                    @endif
                </p>
            </div>
            <div class="report-actions" style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                @if($reportType === 'class')
                    <a href="{{ route('laporan.per-kelas', request()->only(['year', 'from', 'to'])) }}" class="btn btn-secondary">Ringkasan Semua Kelas</a>
                @endif
                <a href="{{ $reportType === 'class' ? route('laporan.kelas.index') : route('laporan.siswa.index') }}" class="btn btn-secondary">Kembali</a>
                <a href="{{ $reportType === 'student' ? route('laporan.siswa.export', array_merge(['student' => $student->id], request()->query())) : route('laporan.kelas.export', array_merge(['schoolClass' => $schoolClass->id], request()->query())) }}" class="btn btn-secondary">Ekspor XLSX</a>
                <button type="button" class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
            </div>
        </div>
    </x-slot>

    @php
        // Keterangan periode aktif (dibawa dari filter halaman indeks) — menggantikan form filter di detail
        $periodLabel = ($filters['year'] ? 'Tahun Ajaran '.$years->firstWhere('id', $filters['year'])?->name : 'Semua Tahun Ajaran')
            .(($filters['from'] || $filters['to']) ? ' · '.($filters['from'] ? \Carbon\Carbon::parse($filters['from'])->translatedFormat('d M Y') : 'awal').' s/d '.($filters['to'] ? \Carbon\Carbon::parse($filters['to'])->translatedFormat('d M Y') : 'sekarang') : '');
    @endphp

    <div class="main-wrap">
        <p class="text-sm text-muted" style="margin:0 0 var(--sp-md);display:flex;align-items:center;gap:6px;flex-wrap:wrap">
            <i data-lucide="calendar" class="icon-sm"></i> Periode: <strong style="color:var(--on-surface)">{{ $periodLabel }}</strong> · Kasus dihitung dari tanggal kejadian (fallback: tanggal dicatat) · Ubah periode dari halaman <a href="{{ $reportType === 'class' ? route('laporan.kelas.index') : route('laporan.siswa.index') }}" style="color:var(--primary);text-decoration:underline">{{ $reportType === 'class' ? 'Laporan Kelas' : 'Laporan Siswa' }}</a>
        </p>

        @if($reportData['students']->isEmpty())
            <div class="card no-print">
                <p class="text-muted" style="text-align:center;margin:0">Belum ada riwayat pada filter yang dipilih.</p>
            </div>
        @endif

        @foreach($reportData['students'] as $group)
            @php
                $reportStudent = $group['student'];
                $caseCount = $group['cases']->count();
                $achCount = $group['achievements']->count();
                $ledgerCount = $group['ledger']->count();
            @endphp
            {{-- Kontainer riwayat 1 siswa: dibuat sebagai <table> dengan kepala abu
                 di <thead> — saat kontainer terpotong ke halaman berikutnya saat
                 print, browser otomatis mengulang <thead> (nama + counter ikut muncul lagi). --}}
            <table class="report-box">
                <thead><tr><td>
                    <div class="report-box-head">
                        <h2 style="margin:0;font-weight:600;font-size:1rem">
                            {{ $reportStudent->full_name }}
                            <span class="text-muted text-sm" style="font-weight:400">· NIS {{ $reportStudent->nis }}</span>
                        </h2>
                        <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                            <span class="count-chip chip-danger">Pelanggaran: {{ $caseCount }}</span>
                            <span class="count-chip chip-success">Prestasi: {{ $achCount }}</span>
                        </div>
                    </div>
                </td></tr></thead>
                <tbody><tr><td class="report-box-cell">

                    <div class="report-section">
                        <h3 style="margin:5px 15px;font-weight:600">Riwayat pelanggaran</h3>
                        <div class="table-wrap" style="margin:10px 20px">
                            <table>
                                <thead><tr><th>Tanggal</th><th>Kasus</th><th>Kelas</th><th>Kategori</th><th>Status</th><th>Pelapor</th><th>Keterangan</th></tr></thead>
                                <tbody>
                                    @forelse($group['cases'] as $case)
                                        <tr>
                                            <td>{{ ($case->incident_date ?? $case->created_at)?->format('d/m/Y') }}</td>
                                            <td class="mono">{{ $case->case_number }}</td>
                                            <td>{{ $case->schoolClass?->name ?? '-' }}</td>
                                            <td>{{ $case->violationCategory?->parent?->name ? $case->violationCategory->parent->name.' / ' : '' }}{{ $case->violationCategory?->name ?? '-' }}</td>
                                            <td>{{ ucfirst($case->status) }}</td>
                                            <td>{{ $case->reporter?->name ?? '-' }}</td>
                                            <td>{{ $case->description ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-muted">Tidak ada kasus pada filter ini.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="report-section">
                        <h3 style="margin:0 15px;font-weight:600">Riwayat prestasi</h3>
                        <div class="table-wrap" style="margin:15px 20px">
                            <table>
                                <thead><tr><th>Tanggal</th><th>Nama kegiatan</th><th>Kelas</th><th>Kategori</th><th>Status</th><th>Verifikator</th><th>Bukti tersimpan</th></tr></thead>
                                <tbody>
                                    @forelse($group['achievements'] as $achievement)
                                        <tr>
                                            <td>{{ ($achievement->event_date ?? $achievement->created_at)?->format('d/m/Y') }}</td>
                                            <td>{{ $achievement->event_name ?: '-' }}</td>
                                            <td>{{ $achievement->schoolClass?->name ?? '-' }}</td>
                                            <td>{{ $achievement->achievementCategory?->parent?->name ? $achievement->achievementCategory->parent->name.' / ' : '' }}{{ $achievement->achievementCategory?->name ?? '-' }}</td>
                                            <td>{{ ucfirst($achievement->status) }}</td>
                                            <td>{{ $achievement->verifier?->name ?? '-' }}</td>
                                            <td>{{ $achievement->evidence_path ? $achievement->evidence_path : 'Tidak tercatat' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-muted">Tidak ada catatan prestasi pada filter ini.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Transaksi poin: tampil di layar saja — dicetak tidak ikut supaya hemat halaman --}}
                    <div class="print-hide">
                        <h3 style="margin:0 15px;font-weight:600">Transaksi poin yang tercatat <span class="text-muted text-sm" style="font-weight:400">({{ $ledgerCount }} transaksi · tidak ikut tercetak)</span></h3>
                        <div class="table-wrap" style="margin:15px 20px">
                            <table>
                                <thead><tr><th>Tanggal</th><th>Jenis transaksi</th><th>Sumber</th><th>Perubahan</th><th>Saldo setelah</th><th>Verifikator</th><th>Catatan</th></tr></thead>
                                <tbody>
                                    @forelse($group['ledger'] as $ledger)
                                        @php
                                            $source = $ledger->source;
                                            $sourceLabel = $source instanceof \App\Models\DisciplineCase
                                                ? 'Kasus '.$source->case_number
                                                : ($source instanceof \App\Models\AchievementRecord
                                                    ? ($source->event_name ?: 'Prestasi #'.$source->id)
                                                    : '-');
                                        @endphp
                                        <tr>
                                            <td>{{ $ledger->created_at?->format('d/m/Y H:i') }}</td>
                                            <td>{{ $ledger->transaction_type }}</td>
                                            <td>{{ $sourceLabel }}</td>
                                            <td class="mono" style="color:{{ $ledger->direction === 'debit' ? 'var(--danger)' : 'var(--success)' }}">
                                                {{ $ledger->direction === 'debit' ? '-' : '+' }}{{ number_format((int) $ledger->amount) }}
                                            </td>
                                            <td class="mono">{{ number_format((int) $ledger->balance_after) }}</td>
                                            <td>{{ $ledger->verifier?->name ?? ($source instanceof \App\Models\DisciplineCase ? $source->validator?->name : null) ?? '-' }}</td>
                                            <td>{{ $ledger->reason ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-muted">Tidak ada transaksi poin pada filter ini.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </td></tr></tbody>
            </table>
        @endforeach
    </div>

    <style>
        /* Kontainer riwayat per siswa: <table> dengan kepala abu di <thead> —
           saat kontainer terpotong ke halaman berikutnya saat print, browser
           otomatis mengulang <thead> (nama + counter ikut muncul lagi). */
        .report-box {
            width:100%; background:#ffffff; border:1px solid #e5e7eb; border-radius:10px;
            border-collapse:separate; border-spacing:0; margin-bottom:var(--sp-lg);
        }
        .report-box > thead > tr > td, .report-box > tbody > tr > td {
            padding:0; border:none; background:transparent;
        }
        .report-box-head {
            display:flex; justify-content:space-between; align-items:center; gap:var(--sp-sm); flex-wrap:wrap;
            padding:var(--sp-sm) var(--sp-md); background:#f3f4f6; border-bottom:1px solid #e5e7eb;
        }
        /* Padding vertikal longgar: judul section dan tabel tidak menempel ujung kontainer */
        .report-box-cell { padding:var(--sp-lg) var(--sp-md); }
        /* Responsif: table-layout fixed membuat lebar kontainer mengikuti viewport,
           tabel di dalamnya scroll di .table-wrap — bukan memaksa halaman melebar di HP. */
        .report-box { table-layout: fixed; }
        /* Kepala tabel isi berwarna abu */
        .report-box-cell .table-wrap thead th {
            background:#f3f4f6; color:#374151;
        }
        .count-chip {
            display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px;
            font-size:.75rem; font-weight:700; border:1px solid; white-space:nowrap;
        }
        .chip-danger { background:#fee2e2; color:#991b1b; border-color:#fecaca; }
        .chip-success { background:#dcfce7; color:#166534; border-color:#bbf7d0; }

        @media print {
            .app-header, .sidebar, .mobile-topbar, .report-actions, .app-footer, .print-hide { display: none !important; }
            .app-shell { margin: 0 !important; }
            .main-wrap { max-width: none !important; padding: 0 !important; }
            /* Kontainer tetap terlihat saat dicetak: garis + kepala abu, warna dipaksa ke printer */
            .report-box { border: 1px solid #9ca3af !important; border-radius: 6px !important; box-shadow: none !important; }
            .report-box > thead > tr > td, .report-box > tbody > tr > td { padding: 0 !important; border: none !important; }
            .report-box-head {
                background: #f3f4f6 !important; border-bottom: 1px solid #9ca3af !important;
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
            .count-chip { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            /* Kepala tabel isi abu saat cetak (warna dipaksa ke printer) */
            .report-box-cell .table-wrap thead th {
                background:#f3f4f6 !important; color:#374151 !important;
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
            /* Hemat halaman: tabel rapat, kontainer panjang boleh menerus ke halaman berikutnya.
               thead otomatis berulang di tiap halaman (kepala abu + counter + kolom tabel). */
            .report-box-cell { padding: var(--sp-sm) var(--sp-md) var(--sp-md) !important; }
            .table-wrap { overflow: visible !important; }
            table { width: 100% !important; font-size: 8pt !important; }
            th, td { padding: 3px 4px !important; }
            thead { display: table-header-group; }
            /* Baris yang dilindungi dari terpotong hanya baris tabel isi, BUKAN baris kontainer */
            .report-box-cell tr { page-break-inside: avoid; }
            /* Satu section (judul + tabel) jangan terpotong di tengah: geser utuh ke halaman berikutnya.
               Browser tetap memecah section yang lebih panjang dari 1 halaman. */
            .report-section { break-inside: avoid; page-break-inside: avoid; }
            @page { size: A4; margin: 12mm; }
        }
    </style>
</x-app-layout>
