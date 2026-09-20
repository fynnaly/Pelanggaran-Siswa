<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Kategori Prestasi</h1>
                <p class="text-muted text-sm">{{ $categories->total() }} total kategori</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('achievement-categories.create') }}" class="btn btn-primary"><i data-lucide="plus" class="icon-sm"></i> Tambah</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
        <div class="toolbar">
            <form method="GET" action="{{ route('achievement-categories.index') }}" style="display:flex;gap:var(--sp-sm)">
                <input type="text" name="search" class="input" placeholder="Cari kode atau nama..." value="{{ $search ?? '' }}" style="max-width:280px">
                <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="search" class="icon-sm"></i> Cari</button>
                @if($search)<a href="{{ route('achievement-categories.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="x" class="icon-sm"></i> Reset</a>@endif
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Kode</th><th>Nama</th><th>Poin</th><th>Status</th><th style="width:100px">Aksi</th></tr></thead>
                <tbody>
                    @forelse($categories as $cat)
                        <tr>
                            <td class="mono">{{ $cat->code }}</td>
                            <td><strong>{{ $cat->name }}</strong></td>
                            <td class="mono">+{{ $cat->points }}</td>
                            <td><span class="badge {{ $cat->status==='active'?'badge-success':'badge-warning' }}">{{ ucfirst($cat->status) }}</span></td>
                            <td><div class="action-cell">
                                <a href="{{ route('achievement-categories.edit', $cat) }}" class="action-btn" title="Edit"><i data-lucide="pencil" style="width:14px;height:14px"></i></a>
                                <form action="{{ route('achievement-categories.destroy', $cat) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kategori ini?')">@csrf @method('DELETE')<button class="action-btn" title="Hapus" style="color:var(--danger)"><i data-lucide="trash-2" style="width:14px;height:14px"></i></button></form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada kategori prestasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-sm);margin-top:var(--sp-lg)">
            <span class="text-sm text-muted">Menampilkan {{ $categories->firstItem() ?? 0 }}-{{ $categories->lastItem() ?? 0 }} dari {{ $categories->total() }}</span>
            <div class="pagination" style="margin-top:0">{{ $categories->links() }}</div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
