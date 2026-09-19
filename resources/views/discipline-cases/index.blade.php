<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Kasus Pelanggaran</div>
        <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-sm)">
            <div>
                <h1>Kasus Pelanggaran</h1>
                <p class="text-muted text-sm">{{ $cases->total() }} total kasus</p>
            </div>
            <a href="{{ route('discipline-cases.create') }}" class="btn btn-primary">Buat Kasus Baru</a>
        </div>
    </x-slot>

    <div class="main-wrap">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <div class="toolbar">
            <input type="text" class="input" placeholder="Cari nomor kasus, nama siswa..." style="max-width:280px">
            <div class="filter-group">
                <select class="input" style="width:auto;min-width:150px">
                    <option>Semua Status</option>
                    <option value="found" {{ $status==='found'?'selected':'' }}>Diproses</option>
                    <option value="validated" {{ $status==='validated'?'selected':'' }}>Divalidasi</option>
                    <option value="done" {{ $status==='done'?'selected':'' }}>Selesai</option>
                    <option value="dismissed" {{ $status==='dismissed'?'selected':'' }}>Dibuang</option>
                </select>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No. Kasus</th>
                        <th>Siswa</th>
                        <th>Pelanggaran</th>
                        <th>Poin</th>
                        <th>Pelapor</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th style="width:100px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cases as $case)
                        <tr>
                            <td class="mono">{{ $case->case_number }}</td>
                            <td>
                                <strong>{{ $case->student?->full_name ?? '-' }}</strong>
                                <br><span class="text-muted text-sm">{{ $case->student?->schoolClass?->name ?? '' }}</span>
                            </td>
                            <td>{{ $case->violationCategory?->name ?? '-' }}</td>
                            <td class="mono">-{{ $case->violationCategory?->points ?? '-' }}</td>
                            <td class="text-sm">{{ $case->reporter?->name ?? '-' }}</td>
                            <td>
                                @if($case->status === 'found')
                                    <span class="badge badge-warning">Diproses</span>
                                @elseif($case->status === 'validated')
                                    <span class="badge badge-warning">Divalidasi</span>
                                @elseif($case->status === 'done')
                                    <span class="badge badge-success">Selesai</span>
                                @else
                                    <span class="badge badge-danger">{{ ucfirst($case->status) }}</span>
                                @endif
                            </td>
                            <td class="text-sm text-muted">{{ $case->created_at?->format('d M Y') }}</td>
                            <td>
                                <div class="action-cell">
                                    <a href="{{ route('discipline-cases.show', $case) }}" class="action-btn" title="Lihat">V</a>
                                    @if($case->status === 'found')
                                        <form action="{{ route('discipline-cases.validate', $case) }}" method="POST" class="inline" onsubmit="return confirm('Validasi kasus ini?')">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="validation_passed" value="1">
                                            <button class="action-btn" title="Validasi" style="color:var(--tertiary)">V</button>
                                        </form>
                                    @endif
                                    @if($case->status === 'validated')
                                        <form action="{{ route('discipline-cases.done', $case) }}" method="POST" class="inline" onsubmit="return confirm('Tandai selesai?')">
                                            @csrf @method('PATCH')
                                            <button class="action-btn" title="Selesai">S</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="padding:40px;text-align:center;color:var(--on-surface-muted)">Belum ada kasus. Klik "Buat Kasus Baru" untuk memulai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-sm);margin-top:var(--sp-lg)">
            <span class="text-sm text-muted">Menampilkan {{ $cases->firstItem() ?? 0 }}-{{ $cases->lastItem() ?? 0 }} dari {{ $cases->total() }} kasus</span>
            <div class="pagination" style="margin-top:0">
                {{ $cases->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
