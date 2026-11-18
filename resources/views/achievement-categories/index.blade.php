<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Kategori Prestasi</h1>
                <p class="text-muted text-sm" data-live-count>{{ $categories->total() }} total kategori</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                @if(auth()->user()->hasAnyRole(['admin', 'bk', 'kesiswaan', 'kepala_sekolah']))
                    <a href="{{ route('achievement-categories.template') }}" class="btn btn-secondary"><i data-lucide="download" class="icon-sm"></i> Template</a>
                    <a href="{{ route('achievement-categories.export') }}" class="btn btn-secondary"><i data-lucide="download" class="icon-sm"></i> Ekspor XLSX</a>
                    <a href="{{ route('achievement-categories.create') }}" class="btn btn-primary"><i data-lucide="plus" class="icon-sm"></i> Tambah</a>
                @endif
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
        <div class="toolbar" style="margin-bottom:var(--sp-md);display:flex;gap:var(--sp-sm);flex-wrap:wrap;align-items:flex-end">
            <form method="GET" action="{{ route('achievement-categories.index') }}" data-live="#categoryRegion" id="categoryFilter" style="display:flex;gap:var(--sp-sm);flex:1;flex-wrap:wrap;align-items:flex-end;min-width:260px">
                <div style="display:flex;flex-direction:column;gap:4px;flex:1;min-width:180px">
                    <label for="category-search" style="font-size:.75rem;font-weight:700;color:var(--on-surface-muted);text-transform:uppercase;letter-spacing:.05em">Cari</label>
                    <input id="category-search" type="text" name="search" class="input" placeholder="Cari kode atau nama..." value="{{ $search ?? '' }}">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="search" class="icon-sm"></i> Cari</button>
                @if($search)<a href="{{ route('achievement-categories.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="x" class="icon-sm"></i> Reset</a>@endif
            </form>
            @if(auth()->user()->hasAnyRole(['admin', 'bk', 'kesiswaan', 'kepala_sekolah']))
                <form method="POST" action="{{ route('achievement-categories.import') }}" enctype="multipart/form-data" style="display:flex;align-items:center;gap:var(--sp-sm);flex:1;flex-wrap:wrap;min-width:260px">
                    @csrf
                    <label class="text-sm text-muted" for="achievement-category-file" style="white-space:nowrap">Impor CSV/XLSX:</label>
                    <input id="achievement-category-file" type="file" name="file" class="input" accept=".csv,.txt,.xlsx" required style="width:auto;padding:6px 10px;flex:1;min-width:0">
                    <button type="submit" class="btn btn-primary btn-sm" style="flex-shrink:0"><i data-lucide="upload" class="icon-sm"></i> Unggah</button>
                </form>
            @endif
        </div>
        <div id="categoryRegion">
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th style="width:48px"><x-sortable-header column="id" label="No" :sort="$sort" :order="$order" route="achievement-categories.index" :params="request()->except('sort','order','page')" /></th>
                    <th><x-sortable-header column="parent" label="Kategori" :sort="$sort" :order="$order" route="achievement-categories.index" :params="request()->except('sort','order','page')" /></th>
                    <th><x-sortable-header column="code" label="Kode" :sort="$sort" :order="$order" route="achievement-categories.index" :params="request()->except('sort','order','page')" /></th>
                    <th><x-sortable-header column="name" label="Nama" :sort="$sort" :order="$order" route="achievement-categories.index" :params="request()->except('sort','order','page')" /></th>
                    <th><x-sortable-header column="points" label="Poin" :sort="$sort" :order="$order" route="achievement-categories.index" :params="request()->except('sort','order','page')" /></th>
                    <th><x-sortable-header column="status" label="Status" :sort="$sort" :order="$order" route="achievement-categories.index" :params="request()->except('sort','order','page')" /></th>
                    <th style="width:100px">Aksi</th>
                </tr></thead>
                <tbody>
                    @forelse($categories as $cat)
                        <tr>
                            <td class="text-sm text-muted">{{ $cat->id }}</td>
                            <td class="text-sm text-muted">@if($cat->parent){{ $cat->parent->name }}@elseif($cat->children_count > 0)<span class="badge badge-info">Induk</span>@else<span class="text-muted">-</span>@endif</td>
                            <td class="mono">{{ $cat->code }}</td>
                            <td><strong>{{ $cat->name }}</strong></td>
                            <td class="mono">+{{ $cat->points }}</td>
                            <td><span class="badge {{ $cat->status==='active'?'badge-success':'badge-warning' }}">{{ ucfirst($cat->status) }}</span></td>
                            <td><div class="action-cell">
                                @if(auth()->user()->hasAnyRole(['admin', 'bk', 'kesiswaan', 'kepala_sekolah']))
                                    <a href="{{ route('achievement-categories.edit', $cat) }}" class="action-btn" title="Edit"><i data-lucide="pencil" style="width:14px;height:14px"></i></a>
                                    <form action="{{ route('achievement-categories.destroy', $cat) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kategori ini?')">@csrf @method('DELETE')<button class="action-btn" title="Hapus" style="color:var(--danger)"><i data-lucide="trash-2" style="width:14px;height:14px"></i></button></form>
                                @else
                                    <span class="text-muted text-sm">—</span>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada kategori prestasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex-wrap:wrap;margin-top:var(--sp-lg)">
            <span class="text-sm text-muted">Menampilkan {{ $categories->firstItem() ?? 0 }}-{{ $categories->lastItem() ?? 0 }} dari {{ $categories->total() }}</span>
            <div class="pagination" style="margin-top:0">{{ $categories->links() }}</div>
        </div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
