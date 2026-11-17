<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Laporan Kelas</h1>
                <p class="text-muted text-sm">
                    Pilih kelas untuk melihat detail riwayat, lalu cetak/PDF/Excel dari halaman detailnya.
                </p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('laporan.siswa.index') }}" class="btn btn-secondary"><i data-lucide="user" class="icon-sm"></i> Laporan Siswa</a>
                <a href="{{ route('laporan.per-kelas', request()->only(['year', 'from', 'to'])) }}" class="btn btn-secondary"><i data-lucide="bar-chart-3" class="icon-sm"></i> Ringkasan Chart</a>
            </div>
        </div>
    </x-slot>

    @php
        // Keterangan periode aktif agar jelas data dihitung mulai/dari mana
        $periodLabel = ($filters['year'] ? 'Tahun Ajaran '.$years->firstWhere('id', $filters['year'])?->name : 'Semua Tahun Ajaran')
            .(($filters['from'] || $filters['to']) ? ' · '.($filters['from'] ? \Carbon\Carbon::parse($filters['from'])->translatedFormat('d M Y') : 'awal').' s/d '.($filters['to'] ? \Carbon\Carbon::parse($filters['to'])->translatedFormat('d M Y') : 'sekarang') : '');
    @endphp

    <div class="main-wrap">
        <div class="toolbar">
            <form method="GET" action="{{ route('laporan.kelas.index') }}" style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;align-items:center;width:100%">
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
                <a href="{{ route('laporan.kelas.index') }}" class="btn btn-secondary btn-sm">Reset</a>
            </form>
        </div>

        <p class="text-sm text-muted" style="margin:0 0 var(--sp-md);display:flex;align-items:center;gap:6px;flex-wrap:wrap">
            <i data-lucide="calendar" class="icon-sm"></i> Periode: <strong style="color:var(--on-surface)">{{ $periodLabel }}</strong> · Kasus dihitung dari tanggal kejadian (fallback: tanggal dicatat)
        </p>

        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th style="width:40px">No</th>
                    <th><x-sortable-header column="class" label="Kelas" :sort="$sort" :order="$order" route="laporan.kelas.index" :params="request()->except('sort','order','page')" /></th>
                    <th><x-sortable-header column="year" label="Tahun Ajaran" :sort="$sort" :order="$order" route="laporan.kelas.index" :params="request()->except('sort','order','page')" /></th>
                    <th><x-sortable-header column="homeroom" label="Wali Kelas" :sort="$sort" :order="$order" route="laporan.kelas.index" :params="request()->except('sort','order','page')" /></th>
                    <th style="text-align:right"><x-sortable-header column="students" label="Jumlah Siswa" :sort="$sort" :order="$order" route="laporan.kelas.index" :params="request()->except('sort','order','page')" /></th>
                    <th style="text-align:right"><x-sortable-header column="kasus" label="Kasus Pelanggaran" :sort="$sort" :order="$order" route="laporan.kelas.index" :params="request()->except('sort','order','page')" /></th>
                    <th style="text-align:right"><x-sortable-header column="prestasi" label="Prestasi" :sort="$sort" :order="$order" route="laporan.kelas.index" :params="request()->except('sort','order','page')" /></th>
                    <th style="width:130px">Aksi</th>
                </tr></thead>
                <tbody>
                    @forelse($rows as $i => $row)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td style="font-weight:600;white-space:nowrap">{{ $row['class']->name }}</td>
                            <td class="text-sm text-muted">{{ $row['class']->academicYear?->name ?? '-' }}</td>
                            <td class="text-sm">{{ $row['class']->homeroomTeacher?->name ?? '-' }}</td>
                            <td class="mono" style="text-align:right">{{ $row['students'] }}</td>
                            <td class="mono" style="text-align:right;font-weight:700;{{ $row['kasus'] > 0 ? 'color:var(--danger)' : '' }}">{{ $row['kasus'] }}</td>
                            <td class="mono" style="text-align:right;color:var(--success)">{{ $row['prestasi'] }}</td>
                            <td>
                                <a href="{{ route('laporan.kelas', array_merge(['schoolClass' => $row['class']->id], $linkParams)) }}" class="btn btn-secondary btn-sm">
                                    <i data-lucide="file-text" class="icon-sm"></i> Detail & Cetak
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted" style="text-align:center;padding:var(--sp-lg)">Belum ada kelas pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
