<x-app-layout>
    <x-slot name="header">

        <div class="page-header">
            <div>
                <h1>Kasus Pelanggaran</h1>
                <p class="text-muted text-sm">{{ $cases->total() }} total kasus</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('kasus-pelanggaran.create') }}" class="btn btn-primary"><i data-lucide="plus" class="icon-sm"></i> Buat Kasus</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if(session('warning'))<div class="alert alert-warning"><i data-lucide="alert-triangle" class="icon-sm"></i> {{ session('warning') }}</div>@endif
        @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
        <div class="toolbar">
            <form method="GET" action="{{ route('kasus-pelanggaran.index') }}" style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;align-items:center;width:100%">
                <input type="text" name="search" class="input" placeholder="Cari nomor kasus, nama siswa..." style="max-width:280px" value="{{ request('search') }}">
                <div class="filter-group">
                    <select name="status" class="input" style="width:auto;min-width:150px" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="found" {{ $status==='found'?'selected':'' }}>Diproses</option>
                        <option value="validated" {{ $status==='validated'?'selected':'' }}>Divalidasi</option>
                        <option value="done" {{ $status==='done'?'selected':'' }}>Selesai</option>
                        <option value="dismissed" {{ $status==='dismissed'?'selected':'' }}>Dibuang</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>No. Kasus</th><th>Siswa</th><th>Pelanggaran</th><th>Poin</th><th>Pelapor</th><th>Status</th><th>Tanggal</th><th style="width:100px">Aksi</th></tr></thead>
                <tbody>
                    @forelse($cases as $case)
                        <tr>
                            <td class="mono">{{ $case->case_number }}</td>
                            <td><strong>{{ $case->student?->full_name ?? '-' }}</strong><br><span class="text-muted text-sm">{{ $case->student?->schoolClass?->name ?? '' }}</span></td>
                            <td>{{ $case->violationCategory?->name ?? '-' }}</td>
                            <td class="mono">-{{ $case->violationCategory?->points ?? '-' }}</td>
                            <td class="text-sm">{{ $case->reporter?->name ?? '-' }}</td>
                            <td>
                                @if($case->status==='found')<span class="badge badge-warning"><i data-lucide="clock" style="width:12px;height:12px"></i> Diproses</span>
                                @elseif($case->status==='validated')<span class="badge" style="background:var(--ws);color:var(--wt)"><i data-lucide="check" style="width:12px;height:12px"></i> Divalidasi</span>
                                @elseif($case->status==='done')<span class="badge badge-success"><i data-lucide="check-circle" style="width:12px;height:12px"></i> Selesai</span>
                                @else<span class="badge badge-danger"><i data-lucide="x-circle" style="width:12px;height:12px"></i> {{ ucfirst($case->status) }}</span>@endif
                            </td>
                            <td class="text-sm text-muted">{{ $case->created_at?->format('d M Y') }}</td>
                            <td><div class="action-cell">
                                <a href="{{ route('kasus-pelanggaran.show', $case) }}" class="action-btn" title="Lihat"><i data-lucide="eye" style="width:14px;height:14px"></i></a>
                                @if($case->status==='found')
                                    <form action="{{ route('kasus-pelanggaran.validate', $case) }}" method="POST" class="inline" onsubmit="return confirm('Validasi kasus ini?')">@csrf @method('PATCH')<input type="hidden" name="validation_passed" value="1"><button class="action-btn" title="Validasi" style="color:var(--tertiary)"><i data-lucide="check" style="width:14px;height:14px"></i></button></form>
                                @endif
                                @if($case->status==='validated')
                                    <form action="{{ route('kasus-pelanggaran.done', $case) }}" method="POST" class="inline" onsubmit="return confirm('Tandai selesai?')">@csrf @method('PATCH')<button class="action-btn" title="Selesai"><i data-lucide="check-circle" style="width:14px;height:14px"></i></button></form>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada kasus.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-sm);margin-top:var(--sp-lg)">
            <span class="text-sm text-muted">Menampilkan {{ $cases->firstItem() ?? 0 }}-{{ $cases->lastItem() ?? 0 }} dari {{ $cases->total() }}</span>
            <div class="pagination" style="margin-top:0">{{ $cases->links() }}</div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
