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

        <div class="toolbar">
            <form action="{{ route('teachers.index') }}" method="GET" data-live="#teacherRegion" id="teacherFilter"
                  class="toolbar-form toolbar-form--filters">
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari nama, NIP, mata pelajaran, jabatan, atau email akun..." class="input toolbar-search">
                <select name="status" class="input w-auto min-w-[130px] max-sm:w-full max-sm:min-w-0">
                    <option value="">Semua status</option>
                    <option value="active" {{ ($status ?? '')==='active'?'selected':'' }}>Aktif</option>
                    <option value="inactive" {{ ($status ?? '')==='inactive'?'selected':'' }}>Nonaktif</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm max-sm:w-full max-sm:justify-center">
                    <i data-lucide="filter" class="icon-sm"></i> Saring
                </button>
                <a href="{{ route('teachers.index') }}" class="btn btn-secondary btn-sm max-sm:w-full max-sm:justify-center" title="Bersihkan semua filter">
                    <i data-lucide="x" class="icon-sm"></i> Reset
                </a>
            </form>
            @if(auth()->user()?->hasAnyRole(['admin','kesiswaan','kepala_sekolah']))
                <form action="{{ route('teachers.import') }}" method="POST" enctype="multipart/form-data"
                      class="toolbar-form toolbar-form--import">
                    @csrf
                    <label class="text-sm text-muted shrink-0 whitespace-nowrap max-sm:w-full">Import CSV/XLSX:</label>
                    <input type="file" name="file" accept=".csv,.txt,.xlsx" required class="input max-sm:w-full" style="padding:6px 10px">
                    <button type="submit" class="btn btn-primary btn-sm max-sm:w-full max-sm:justify-center"><i data-lucide="upload" class="icon-sm"></i> Unggah</button>
                </form>
            @endif
        </div>

        <p class="text-xs text-muted" style="margin-top:6px">
            NIP boleh kosong. Kolom wajib hanya Nama Lengkap/NAMA — format spreadsheet sekolah
            (NO, NAMA, JABATAN SEKARANG) juga diterima; kolom JABATAN SEKARANG dibaca sebagai Jabatan.
        </p>

        <div id="teacherRegion">@include('teachers._table')</div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
