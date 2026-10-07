<x-app-layout>
    <x-slot name="header">

        <div class="page-header">
            <div><h1>Kategori Pelanggaran</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('violation-categories.create') }}" class="btn btn-primary"><i data-lucide="plus" class="icon-sm"></i> Tambah</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th style="width:48px"><x-sortable-header column="id" label="No" :sort="$sort" :order="$order" route="violation-categories.index" /></th>
                    <th><x-sortable-header column="code" label="Kode" :sort="$sort" :order="$order" route="violation-categories.index" /></th>
                    <th><x-sortable-header column="name" label="Nama" :sort="$sort" :order="$order" route="violation-categories.index" /></th>
                    <th>Induk Kategori</th>
                    <th><x-sortable-header column="severity" label="Tingkat" :sort="$sort" :order="$order" route="violation-categories.index" /></th>
                    <th><x-sortable-header column="points" label="Poin" :sort="$sort" :order="$order" route="violation-categories.index" /></th>
                    <th><x-sortable-header column="status" label="Status" :sort="$sort" :order="$order" route="violation-categories.index" /></th>
                    <th style="width:100px">Aksi</th>
                </tr></thead>
                <tbody>
                    @forelse($categories as $cat)
                        <tr>
                            <td class="text-sm text-muted">{{ $cat->id }}</td>
                            <td class="mono">{{ $cat->code }}</td>
                            <td><strong>{{ $cat->name }}</strong></td>
                            <td class="text-sm text-muted">@if($cat->parent){{ $cat->parent->name }}@else<span class="badge badge-info">Induk</span>@endif</td>
                            <td><span class="badge badge-warning">{{ $cat->severity }}</span></td>
                            <td class="mono">{{ $cat->points }}</td>
                            <td><span class="badge {{ $cat->status==='active'?'badge-success':'' }}" style="{{ $cat->status!=='active'?'background:var(--surface);color:var(--on-surface-muted)':'' }}">{{ ucfirst($cat->status) }}</span></td>
                            <td><div class="action-cell">
                                <a href="{{ route('violation-categories.edit', $cat) }}" class="action-btn" title="Edit"><i data-lucide="pencil" style="width:14px;height:14px"></i></a>
                                <form action="{{ route('violation-categories.destroy', $cat) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kategori ini?')">@csrf @method('DELETE')<button class="action-btn" title="Hapus" style="color:var(--danger)"><i data-lucide="trash-2" style="width:14px;height:14px"></i></button></form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada kategori.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display:flex;justify-content:center;margin-top:var(--sp-lg)"><div class="pagination" style="margin-top:0">{{ $categories->links() }}</div></div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>