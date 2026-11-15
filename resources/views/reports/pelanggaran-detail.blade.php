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
        $statusLabel = [
            'found' => 'Temuan',
            'clarifying' => 'Klarifikasi',
            'classifying' => 'Klasifikasi',
            'handling' => 'Penanganan',
            'monitoring' => 'Monitoring',
            'closed' => 'Selesai',
            'dismissed' => 'Dibuang',
        ];
        $studentStatusBadge = fn ($st) => match ($st) {
            'active' => 'badge-success',
            'graduated' => 'badge-info',
            'transferred' => 'badge-warning',
            default => 'badge-neutral',
        };
        $studentStatusLabel = [
            'active' => 'Aktif',
            'graduated' => 'Lulus',
            'transferred' => 'Pindah',
            'inactive' => 'Nonaktif',
        ];
        $sanksi = $category->resolveSanksi();
    @endphp

    <div class="main-wrap">
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th><x-sortable-header column="nama" label="Siswa" :sort="$sort" :order="$order" route="laporan.pelanggaran.kategori" :params="$headParams" /></th>
                    <th>Status Siswa</th>
                    <th><x-sortable-header column="kelas" label="Kelas" :sort="$sort" :order="$order" route="laporan.pelanggaran.kategori" :params="$headParams" /></th>
                    <th>Tahun Ajaran</th>
                    <th><x-sortable-header column="tanggal" label="Tanggal" :sort="$sort" :order="$order" route="laporan.pelanggaran.kategori" :params="$headParams" /></th>
                    <th>Status Kasus</th>
                    <th>Poin</th>
                    <th>Sanksi</th>
                    <th>No. Kasus</th>
                </tr></thead>
                <tbody>
                    @forelse($cases as $c)
                        <tr>
                            <td>
                                <a href="{{ route('students.show', $c->student_id) }}" style="font-weight:600;text-decoration:none;color:inherit">{{ $c->full_name }}</a>
                                <div class="text-muted text-sm mono">NISN: {{ $c->nisn ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $studentStatusBadge($c->student_status) }}">
                                    {{ $studentStatusLabel[$c->student_status] ?? ucfirst($c->student_status ?? '-') }}
                                </span>
                            </td>
                            <td>{{ $c->class_name ?? 'Tanpa Kelas' }}</td>
                            <td class="text-sm">{{ $c->academic_year_name ?? '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($c->tanggal)->format('d/m/Y') }}</td>
                            <td><span class="badge {{ $statusBadge($c->status) }}">{{ $statusLabel[$c->status] ?? ucfirst($c->status) }}</span></td>
                            <td class="mono" style="font-weight:700;color:var(--danger)">
                                @if($c->points_deducted !== null)
                                    -{{ (int) $c->points_deducted }}
                                @elseif($c->points_override !== null)
                                    -{{ (int) $c->points_override }}
                                @else
                                    -{{ (int) $category->points }}
                                @endif
                            </td>
                            <td class="text-sm text-muted" style="max-width:220px">{{ $sanksi }}</td>
                            <td><a href="{{ route('kasus-pelanggaran.show', $c->id) }}" class="mono">{{ $c->case_number }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-muted" style="text-align:center;padding:var(--sp-lg)">Belum ada kasus pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $cases->links() }}</div>
    </div>

    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
