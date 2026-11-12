<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>{{ $category->name }}</h1>
                <p class="text-muted text-sm">
                    <span class="mono">{{ $category->code }}</span>
                    @if($category->parent) &bull; {{ $category->parent->name }} @endif
                    &bull; {{ $cases->total() }} kasus
                </p>
            </div>
            <a href="{{ route('laporan.pelanggaran', $linkParams) }}" class="btn btn-secondary"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali ke laporan</a>
        </div>
    </x-slot>

    @php
        $headParams = array_merge(['violationCategory' => $category->id], $linkParams);
        $statusBadge = fn ($s) => match ($s) {
            'closed' => 'badge-success',
            'found', 'clarifying', 'classifying', 'monitoring' => 'badge-warning',
            default => 'badge-info',
        };
    @endphp

    <div class="main-wrap">
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th><x-sortable-header column="nama" label="Siswa" :sort="$sort" :order="$order" route="laporan.pelanggaran.kategori" :params="$headParams" /></th>
                    <th><x-sortable-header column="kelas" label="Kelas" :sort="$sort" :order="$order" route="laporan.pelanggaran.kategori" :params="$headParams" /></th>
                    <th><x-sortable-header column="tanggal" label="Tanggal" :sort="$sort" :order="$order" route="laporan.pelanggaran.kategori" :params="$headParams" /></th>
                    <th>Status</th>
                    <th>No. Kasus</th>
                </tr></thead>
                <tbody>
                    @forelse($cases as $c)
                        <tr>
                            <td><a href="{{ route('students.show', $c->student_id) }}" style="font-weight:600;text-decoration:none;color:inherit">{{ $c->full_name }}</a></td>
                            <td>{{ $c->class_name ?? 'Tanpa Kelas' }}</td>
                            <td>{{ \Carbon\Carbon::parse($c->tanggal)->format('d/m/Y') }}</td>
                            <td><span class="badge {{ $statusBadge($c->status) }}">{{ $c->status }}</span></td>
                            <td><a href="{{ route('kasus-pelanggaran.show', $c->id) }}" class="mono">{{ $c->case_number }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted" style="text-align:center;padding:var(--sp-lg)">Belum ada kasus pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $cases->links() }}</div>
    </div>

    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
