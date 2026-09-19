<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Daftar Kelas</h1><p class="text-muted text-sm">{{ $classes->total() }} kelas terdaftar</p></div>
            <a href="{{ route('classes.create') }}" class="btn btn-primary"><i data-lucide="plus" class="icon-sm"></i> Tambah</a>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nama Kelas</th><th>Tahun Ajaran</th><th>Jumlah Siswa</th><th>Wali Kelas</th><th style="width:100px">Aksi</th></tr></thead>
                <tbody>
                    @forelse($classes as $class)
                        <tr>
                            <td><a href="{{ route('students.index', ['class_id' => $class->id]) }}" style="font-weight:600">{{ $class->name }}</a></td>
                            <td class="text-sm text-muted">{{ $class->academicYear?->name ?? '-' }}</td>
                            <td class="mono" style="font-weight:700">{{ $class->students_count }}</td>
                            <td class="text-sm text-muted">{{ $class->homeroomTeacher?->name ?? '-' }}</td>
                            <td><div class="action-cell">
                                <a href="{{ route('classes.edit', $class) }}" class="action-btn" title="Edit"><i data-lucide="pencil" style="width:14px;height:14px"></i></a>
                                <form action="{{ route('classes.destroy', $class) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kelas {{ $class->name }}?')">@csrf @method('DELETE')<button class="action-btn" title="Hapus" style="color:var(--danger)"><i data-lucide="trash-2" style="width:14px;height:14px"></i></button></form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada kelas. Tambah dulu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:var(--sp-lg)"><div class="pagination" style="margin-top:0">{{ $classes->links() }}</div></div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>