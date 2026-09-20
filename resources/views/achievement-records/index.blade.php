<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Rekam Prestasi</h1>
                <p class="text-muted text-sm">{{ $records->total() }} total rekam</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('achievement-records.create') }}" class="btn btn-primary"><i data-lucide="plus" class="icon-sm"></i> Tambah</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if(session('warning'))<div class="alert alert-warning"><i data-lucide="alert-triangle" class="icon-sm"></i> {{ session('warning') }}</div>@endif
        @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
        <div class="toolbar">
            <form method="GET" action="{{ route('achievement-records.index') }}" style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;align-items:center;width:100%">
                <input type="text" name="search" class="input" placeholder="Cari rekam prestasi..." style="max-width:280px" value="{{ request('search') }}">
                <div class="filter-group">
                    <select name="status" class="input" style="width:auto;min-width:150px" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ $status==='pending'?'selected':'' }}>Pending</option>
                        <option value="verified" {{ $status==='verified'?'selected':'' }}>Divalidasi</option>
                        <option value="rejected" {{ $status==='rejected'?'selected':'' }}>Ditolak</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Siswa</th><th>Kategori</th><th>Poin</th><th>Perekam</th><th>Status</th><th>Tanggal</th><th style="width:80px">Aksi</th></tr></thead>
                <tbody>
                    @forelse($records as $rec)
                        <tr>
                            <td><strong>{{ $rec->student?->full_name ?? '-' }}</strong><br><span class="text-muted text-sm">{{ $rec->student?->schoolClass?->name ?? '' }}</span></td>
                            <td>{{ $rec->achievementCategory?->name ?? '-' }}</td>
                            <td class="mono" style="color:var(--tertiary)">+{{ $rec->achievementCategory?->points ?? '-' }}</td>
                            <td class="text-sm">{{ $rec->recorder?->name ?? '-' }}</td>
                            <td>
                                @if($rec->status==='pending')<span class="badge badge-warning"><i data-lucide="clock" style="width:12px;height:12px"></i> Pending</span>
                                @elseif($rec->status==='verified')<span class="badge badge-success"><i data-lucide="check-circle" style="width:12px;height:12px"></i> Divalidasi</span>
                                @else<span class="badge badge-danger"><i data-lucide="x-circle" style="width:12px;height:12px"></i> Ditolak</span>@endif
                            </td>
                            <td class="text-sm text-muted">{{ $rec->created_at?->format('d M Y') }}</td>
                            <td><div class="action-cell">
                                <a href="{{ route('achievement-records.show', $rec) }}" class="action-btn" title="Lihat"><i data-lucide="eye" style="width:14px;height:14px"></i></a>
                                @if($rec->status==='pending')
                                    <form action="{{ route('achievement-records.validate', $rec) }}" method="POST" class="inline" onsubmit="return confirm('Validasi prestasi ini? Poin akan ditambahkan.')">@csrf @method('PATCH')<button class="action-btn" title="Validasi" style="color:var(--tertiary)"><i data-lucide="check" style="width:14px;height:14px"></i></button></form>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada rekam prestasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-sm);margin-top:var(--sp-lg)">
            <span class="text-sm text-muted">Menampilkan {{ $records->firstItem() ?? 0 }}-{{ $records->lastItem() ?? 0 }} dari {{ $records->total() }}</span>
            <div class="pagination" style="margin-top:0">{{ $records->links() }}</div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
