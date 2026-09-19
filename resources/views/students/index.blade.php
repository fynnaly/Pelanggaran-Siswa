<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Siswa</div>
        <div class="page-header">
            <div>
                <h1>Daftar Siswa</h1>
                <p class="text-muted text-sm">{{ $students->total() }} siswa terdaftar</p>
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
                <a href="{{ route('students.index') }}" class="text-sm" style="color:var(--on-surface-muted)"><i data-lucide="x" class="icon-sm"></i> Hapus filter</a>
            </div>
        @endif

        <div class="toolbar">
            <form action="{{ route('students.index') }}" method="GET" style="display:flex;gap:var(--sp-sm);flex:1;flex-wrap:wrap">
                @if($classId)<input type="hidden" name="class_id" value="{{ $classId }}">@endif
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / NISN / NIS..." class="input" style="max-width:280px">
                <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="search" class="icon-sm"></i> Cari</button>
            </form>
            <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data" style="display:flex;align-items:center;gap:var(--sp-sm)">
                @csrf
                <label class="text-sm text-muted" style="white-space:nowrap">Import CSV:</label>
                <input type="file" name="file" accept=".csv,.txt" required class="input" style="width:auto;padding:6px 10px">
                <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="upload" class="icon-sm"></i> Unggah</button>
            </form>
        </div>

        <form action="{{ route('classes.promote') }}" method="POST" id="bulkPromoteForm" style="display:none;margin-bottom:var(--sp-md)">
            @csrf
            <div class="card" style="display:flex;align-items:center;gap:var(--sp-md);flex-wrap:wrap">
                <span class="text-sm" style="font-weight:600" id="selectedCount">0</span>
                <span class="text-sm text-muted">siswa dipilih</span>
                <span style="color:var(--border)">|</span>
                <label class="text-sm" style="font-weight:600">Pindah ke:</label>
                <select name="class_id" required class="input" style="width:auto;min-width:150px">
                    <option value="">Pilih kelas...</option>
                    @foreach($classesForPromote as $id => $name)
                        @if($classId && $id == $classId) @continue @endif
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="arrow-up" class="icon-sm"></i> Naik Kelas</button>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead><tr><th style="width:40px"><input type="checkbox" id="selectAll" class="checkbox"></th><th>NISN</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Status</th><th>Poin</th><th style="width:80px">Aksi</th></tr></thead>
                <tbody>
                    @forelse($students as $student)
                        @php $poin = $student->pointLedgers->first()?->balance_after ?? 2000; @endphp
                        <tr>
                            <td><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-cb checkbox"></td>
                            <td class="mono">{{ $student->nisn }}</td>
                            <td class="mono">{{ $student->nis }}</td>
                            <td><strong>{{ $student->full_name }}</strong></td>
                            <td class="text-sm">{{ $student->schoolClass?->name ?? '-' }}</td>
                            <td><span class="badge {{ $student->status==='active'?'badge-success':'' }}" style="{{ $student->status!=='active'?'background:var(--surface);color:var(--on-surface-muted)':'' }}">{{ ucfirst($student->status) }}</span></td>
                            <td><span class="mono" style="font-weight:700;{{ $poin<=500?'color:var(--danger)':($poin<=1000?'color:var(--accent)':'color:var(--tertiary)') }}">{{ $poin }}</span></td>
                            <td><div class="action-cell">
                                <a href="{{ route('students.edit', $student) }}" class="action-btn" title="Edit"><i data-lucide="pencil" style="width:14px;height:14px"></i></a>
                                <form action="{{ route('students.destroy', $student) }}" method="POST" class="inline" onsubmit="return confirm('Hapus {{ $student->full_name }}?')">@csrf @method('DELETE')<button class="action-btn" title="Hapus" style="color:var(--danger)"><i data-lucide="trash-2" style="width:14px;height:14px"></i></button></form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>@if(request('q'))Tidak ada yang cocok "@{{ request('q') }}".@elseBelum ada siswa.@endif</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-sm);margin-top:var(--sp-lg)">
            <span class="text-sm text-muted">Menampilkan {{ $students->firstItem() ?? 0 }}-{{ $students->lastItem() ?? 0 }} dari {{ $students->total() }}</span>
            <div class="pagination" style="margin-top:0">{{ $students->links() }}</div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
    <script>
        document.addEventListener('DOMContentLoaded',()=>{
            const s=document.getElementById('selectAll'),c=document.querySelectorAll('.student-cb'),f=document.getElementById('bulkPromoteForm'),n=document.getElementById('selectedCount');
            function u(){const x=document.querySelectorAll('.student-cb:checked').length;n.textContent=x;f.style.display=x===0?'none':'';}
            s?.addEventListener('change',()=>{c.forEach(cb=>{cb.checked=s.checked});u();});
            c.forEach(cb=>{cb.addEventListener('change',u);});
        });
    </script>
</x-app-layout>
