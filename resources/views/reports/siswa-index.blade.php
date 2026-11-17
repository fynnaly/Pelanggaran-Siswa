<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Laporan Siswa</h1>
                <p class="text-muted text-sm">
                    Cari siswa untuk melihat detail riwayat, lalu cetak/PDF/Excel dari halaman detailnya.
                </p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('laporan.kelas.index') }}" class="btn btn-secondary"><i data-lucide="users" class="icon-sm"></i> Laporan Kelas</a>
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
            <form method="GET" action="{{ route('laporan.siswa.index') }}" style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;align-items:center;width:100%">
                <input type="search" name="search" class="input" value="{{ $search }}" placeholder="Cari nama, NIS, atau NISN" aria-label="Cari siswa" style="width:auto;min-width:200px" data-autosearch>
                <select name="year" class="input" style="width:auto;min-width:150px" onchange="this.form.submit()">
                    <option value="">Semua Tahun Ajaran</option>
                    @foreach($years as $year)
                        <option value="{{ $year->id }}" {{ (string) $filters['year'] === (string) $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                    @endforeach
                </select>
                <select name="class" class="input" style="width:auto;min-width:140px" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    @foreach($classes as $schoolClass)
                        <option value="{{ $schoolClass->id }}" {{ (string) $filters['class'] === (string) $schoolClass->id ? 'selected' : '' }}>{{ $schoolClass->name }}</option>
                    @endforeach
                </select>
                <span class="text-muted text-sm" style="white-space:nowrap">Tanggal <strong>mulai</strong></span>
                <input type="date" name="from" class="input" style="width:auto" value="{{ $filters['from'] }}" title="Tanggal mulai (kasus pada tanggal ini ikut dihitung)" onchange="this.form.submit()">
                <span class="text-muted text-sm" style="white-space:nowrap">s/d <strong>selesai</strong></span>
                <input type="date" name="to" class="input" style="width:auto" value="{{ $filters['to'] }}" title="Tanggal selesai (kasus pada tanggal ini ikut dihitung)" onchange="this.form.submit()">
                <a href="{{ route('laporan.siswa.index') }}" class="btn btn-secondary btn-sm">Reset</a>
            </form>
        </div>

        <p class="text-sm text-muted" style="margin:0 0 var(--sp-md);display:flex;align-items:center;gap:6px;flex-wrap:wrap">
            <i data-lucide="calendar" class="icon-sm"></i> Periode: <strong style="color:var(--on-surface)">{{ $periodLabel }}</strong> · Kasus dihitung dari tanggal kejadian (fallback: tanggal dicatat)
        </p>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><x-sortable-header column="name" label="Siswa" :sort="$sort" :order="$order" route="laporan.siswa.index" :params="request()->except('sort','order','page')" /></th>
                        <th><x-sortable-header column="nis" label="NIS" :sort="$sort" :order="$order" route="laporan.siswa.index" :params="request()->except('sort','order','page')" /></th>
                        <th><x-sortable-header column="class" label="Kelas" :sort="$sort" :order="$order" route="laporan.siswa.index" :params="request()->except('sort','order','page')" /></th>
                        <th style="text-align:right"><x-sortable-header column="kasus" label="Pelanggaran" :sort="$sort" :order="$order" route="laporan.siswa.index" :params="request()->except('sort','order','page')" /></th>
                        <th style="text-align:right"><x-sortable-header column="prestasi" label="Prestasi" :sort="$sort" :order="$order" route="laporan.siswa.index" :params="request()->except('sort','order','page')" /></th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td>
                                <strong>{{ $student->full_name }}</strong>
                                <div class="text-muted text-sm">NISN: {{ $student->nisn ?? '-' }}</div>
                            </td>
                            <td class="mono">{{ $student->nis }}</td>
                            <td>{{ $student->currentEnrollment?->schoolClass?->name ?? '-' }}</td>
                            <td style="text-align:right">
                                <span class="count-chip {{ $student->kasus_count > 0 ? 'chip-danger' : 'chip-muted' }}">Pelanggaran: {{ $student->kasus_count }}</span>
                            </td>
                            <td style="text-align:right">
                                <span class="count-chip {{ $student->prestasi_count > 0 ? 'chip-success' : 'chip-muted' }}">Prestasi: {{ $student->prestasi_count }}</span>
                            </td>
                            <td>
                                <a class="btn btn-secondary btn-sm" href="{{ route('laporan.siswa', array_merge(['student' => $student->id], $linkParams)) }}">
                                    <i data-lucide="file-text" class="icon-sm"></i> Detail & Cetak
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted" style="text-align:center;padding:var(--sp-lg)">Tidak ada siswa yang cocok dengan pencarian dan cakupan akses ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $students->links() }}</div>
    </div>

    <style>
        .count-chip {
            display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px;
            font-size:.75rem; font-weight:700; border:1px solid; white-space:nowrap;
        }
        .chip-danger { background:#fee2e2; color:#991b1b; border-color:#fecaca; }
        .chip-success { background:#dcfce7; color:#166534; border-color:#bbf7d0; }
        .chip-muted { background:#f3f4f6; color:#6b7280; border-color:#e5e7eb; }
    </style>
    <script>document.addEventListener('DOMContentLoaded',()=>{
        lucide.createIcons();
        // Search otomatis: submit dengan debounce 500ms saat pengguna selesai mengetik
        const searchInput = document.querySelector('[data-autosearch]');
        if (searchInput) {
            let timer = null;
            searchInput.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => searchInput.form.submit(), 500);
            });
        }
    })</script>
</x-app-layout>
