<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Kategori Pelanggaran</div>
        <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-sm)">
            <div><h1>Kategori Pelanggaran</h1><p class="text-muted text-sm">{{ $categories->total() }} kategori</p></div>
            <a href="{{ route('violation-categories.create') }}" class="btn btn-primary">Tambah Kategori</a>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
        <div class="table-wrap">
            <table>
                <thead><tr><th>Kode</th><th>Nama</th><th>Tingkat</th><th>Poin</th><th>Status</th><th style="width:120px">Aksi</th></tr></thead>
                <tbody>
                    @forelse($categories as $cat)
                        <tr>
                            <td class="mono">{{ $cat->code }}</td>
                            <td><strong>{{ $cat->name }}</strong></td>
                            <td><span class="badge badge-warning" style="background:var(--ws);color:var(--wt)" {{ $cat->severity=='ringan' }}>{{ $cat->severity }}</span></td>
                            <td class="mono">{{ $cat->points }}</td>
                            <td><span class="badge {{ $cat->status=='active' ? 'badge-success' : 'text-muted' }}" style="{{ $cat->status=='active' ? 'background:var(--bs);color:var(--bt)' : 'background:var(--surface);color:var(--on-surface-muted)' }}">{{ ucfirst($cat->status) }}</span></td>
                            <td><div class="action-cell">
                                <a href="{{ route('violation-categories.edit', $cat) }}" class="action-btn" title="Edit">E</a>
                                <form action="{{ route('violation-categories.destroy', $cat) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kategori ini?')">@csrf @method('DELETE')<button class="action-btn" title="Hapus" style="color:var(--danger)">X</button></form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="padding:40px;text-align:center;color:var(--on-surface-muted)">Belum ada kategori. Tambah dulu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:var(--sp-lg)"><div class="pagination" style="margin-top:0">{{ $categories->links() }}</div></div>
    </div>
</x-app-layout>