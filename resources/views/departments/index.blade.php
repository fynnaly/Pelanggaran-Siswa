<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Jurusan / Program Keahlian</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                @if(auth()->user()?->hasAnyRole(['admin','kesiswaan','kepala_sekolah']))
                    <a href="{{ route('jurusan.create') }}" class="btn btn-primary"><i data-lucide="plus" class="icon-sm"></i> Tambah</a>
                @endif
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif

        <form method="GET" action="{{ route('jurusan.index') }}" style="display:flex;gap:var(--sp-sm);margin-bottom:var(--sp-md)">
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari kode atau nama jurusan..."
                   style="flex:1;max-width:320px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--surface);color:var(--on-surface)">
            <button type="submit" class="btn btn-secondary"><i data-lucide="search" class="icon-sm"></i> Cari</button>
            @if($q)<a href="{{ route('jurusan.index') }}" class="btn btn-secondary">Reset</a>@endif
        </form>

        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th style="width:48px"><x-sortable-header column="id" label="No" :sort="$sort" :order="$order" route="jurusan.index" :params="request()->except('sort','order','page','q')" /></th>
                    <th><x-sortable-header column="code" label="Kode" :sort="$sort" :order="$order" route="jurusan.index" :params="request()->except('sort','order','page','q')" /></th>
                    <th><x-sortable-header column="name" label="Nama Jurusan" :sort="$sort" :order="$order" route="jurusan.index" :params="request()->except('sort','order','page','q')" /></th>
                    <th>Jumlah Kelas</th>
                    <th><x-sortable-header column="status" label="Status" :sort="$sort" :order="$order" route="jurusan.index" :params="request()->except('sort','order','page','q')" /></th>
                    <th style="width:140px">Aksi</th>
                </tr></thead>
                <tbody>
                    @forelse($departments as $dept)
                        <tr>
                            <td class="text-sm text-muted">{{ $dept->id }}</td>
                            <td><strong>{{ $dept->code }}</strong></td>
                            <td>{{ $dept->name }}</td>
                            <td class="text-sm text-muted">{{ $dept->classes_count }}</td>
                            <td><span class="badge {{ $dept->status === 'active' ? 'badge-success' : '' }}" style="{{ $dept->status !== 'active' ? 'background:var(--surface);color:var(--on-surface-muted)' : '' }}">{{ $dept->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td><div class="action-cell">
                                <a href="{{ route('jurusan.show', $dept) }}" class="action-btn" title="Detail"><i data-lucide="eye" style="width:14px;height:14px"></i></a>
                                @if(auth()->user()?->hasAnyRole(['admin','kesiswaan','kepala_sekolah']))
                                    <a href="{{ route('jurusan.edit', $dept) }}" class="action-btn" title="Edit"><i data-lucide="pencil" style="width:14px;height:14px"></i></a>
                                    <form action="{{ route('jurusan.toggle-status', $dept) }}" method="POST" class="inline" onsubmit="return confirm('{{ $dept->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }} jurusan {{ $dept->code }}?')">@csrf @method('PATCH')<button class="action-btn" title="Toggle Status"><i data-lucide="power" style="width:14px;height:14px"></i></button></form>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada jurusan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($departments->hasPages())<div style="display:flex;justify-content:center;margin-top:var(--sp-lg)"><div class="pagination" style="margin-top:0">{{ $departments->links() }}</div></div>@endif
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
