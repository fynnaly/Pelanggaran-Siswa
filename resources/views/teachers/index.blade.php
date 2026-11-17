<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Data Guru</h1>
                <p class="text-muted text-sm" data-live-count>{{ $teachers->total() }} guru terdaftar</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('teachers.template') }}" class="btn btn-secondary btn-sm"><i data-lucide="file-down" class="icon-sm"></i> Template</a>
                <a href="{{ route('teachers.export') }}" class="btn btn-secondary btn-sm"><i data-lucide="download" class="icon-sm"></i> Export</a>
                @if(auth()->user()?->hasAnyRole(['admin','kesiswaan','kepala_sekolah']))
                    <a href="{{ route('teachers.create') }}" class="btn btn-primary"><i data-lucide="user-plus" class="icon-sm"></i> Tambah</a>
                @endif
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif

        <div class="toolbar" style="display:flex;flex-direction:column;gap:var(--sp-sm);align-items:stretch;margin-bottom:var(--sp-md)">
            <form action="{{ route('teachers.index') }}" method="GET" data-live="#teacherRegion" id="teacherFilter"
                  style="display:flex;flex-wrap:wrap;gap:var(--sp-sm);align-items:flex-end;width:100%">
                <div style="display:flex;flex-direction:column;gap:4px;flex:2 1 260px;min-width:220px">
                    <label style="font-size:.75rem;font-weight:700;color:var(--on-surface-muted);text-transform:uppercase;letter-spacing:.05em">Cari</label>
                    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Nama, NIP, mata pelajaran, atau email akun..." class="input">
                </div>
                <div style="display:flex;flex-direction:column;gap:4px;flex:1 1 180px;min-width:180px">
                    <label style="font-size:.75rem;font-weight:700;color:var(--on-surface-muted);text-transform:uppercase;letter-spacing:.05em">Status</label>
                    <select name="status" class="input">
                        <option value="">Semua status</option>
                        <option value="active" {{ ($status ?? '')==='active'?'selected':'' }}>Aktif</option>
                        <option value="inactive" {{ ($status ?? '')==='inactive'?'selected':'' }}>Nonaktif</option>
                    </select>
                </div>
            </form>
            @if(auth()->user()?->hasAnyRole(['admin','kesiswaan','kepala_sekolah']))
                <form action="{{ route('teachers.import') }}" method="POST" enctype="multipart/form-data"
                      style="display:flex;align-items:center;gap:var(--sp-sm);flex-wrap:wrap;width:100%;max-width:560px">
                    @csrf
                    <label class="text-sm text-muted" style="white-space:nowrap">Import CSV/XLSX:</label>
                    <input type="file" name="file" accept=".csv,.txt,.xlsx" required class="input" style="width:auto;padding:6px 10px;flex:1;min-width:0">
                    <button type="submit" class="btn btn-primary btn-sm" style="flex-shrink:0"><i data-lucide="upload" class="icon-sm"></i> Unggah</button>
                </form>
            @endif
        </div>

        <div id="teacherRegion">@include('teachers._table')</div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
