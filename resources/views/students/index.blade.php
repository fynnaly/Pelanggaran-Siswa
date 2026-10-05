<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Daftar Siswa</h1>
                <p class="text-muted text-sm" id="studentsTotal" data-live-count>{{ $students->total() }} siswa terdaftar</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('students.template') }}" class="btn btn-secondary btn-sm"><i data-lucide="file-down" class="icon-sm"></i> Template</a>
                <a href="{{ route('students.export') }}" class="btn btn-secondary btn-sm"><i data-lucide="download" class="icon-sm"></i> Export</a>
                <a href="{{ route('students.create') }}" class="btn btn-primary"><i data-lucide="user-plus" class="icon-sm"></i> Tambah</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif

        @if($classId)
            <div style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-md)">
                <span class="badge" style="background:var(--bs);color:var(--bt)"><i data-lucide="filter" class="icon-sm"></i> Kelas {{ $students->first()?->schoolClass?->name ?? '#' }}</span>
                <a href="{{ route('students.index') }}" class="text-sm" style="color:var(--on-surface-muted);white-space:nowrap;flex-shrink:0"><i data-lucide="x" class="icon-sm"></i> Hapus filter</a>
            </div>
        @endif

        <div class="toolbar">
            <form id="studentFilter" action="{{ route('students.index') }}" method="GET" data-live="#studentRegion" style="display:flex;gap:var(--sp-sm);flex:1;align-items:stretch">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / NISN / NIS..." class="input" style="flex:1;min-width:0" id="searchInput">
                <select name="year_id" class="input" style="width:auto;min-width:130px;flex-shrink:0" onchange="this.form.submit()">
                    <option value="">Semua Tahun</option>
                    @foreach($academicYears as $yr)
                        <option value="{{ $yr->id }}" {{ $selectedYearId==$yr->id?'selected':'' }}>{{ $yr->name }}{{ $yr->is_active?' (Aktif)':'' }}</option>
                    @endforeach
                </select>
                <select name="class_id" class="input" style="width:auto;min-width:140px;flex-shrink:0" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    @foreach($allClasses as $cls)
                        <option value="{{ $cls->id }}" {{ request('class_id')==$cls->id?'selected':'' }}>{{ $cls->name }}</option>
                    @endforeach
                </select>
            </form>
            <div style="display:flex;align-items:center;gap:var(--sp-sm);flex-wrap:wrap">
                <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data" style="display:flex;align-items:center;gap:var(--sp-sm);flex:1;min-width:0">
                    @csrf
                    <label class="text-sm text-muted" style="white-space:nowrap">Import CSV:</label>
                    <input type="file" name="file" accept=".csv,.txt" required class="input" style="width:auto;padding:6px 10px;flex:1;min-width:0">
                    <button type="submit" class="btn btn-primary btn-sm" style="flex-shrink:0"><i data-lucide="upload" class="icon-sm"></i> Unggah</button>
                </form>
            </div>
        </div>

        <div id="studentRegion">@include('students._table')</div>

    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
