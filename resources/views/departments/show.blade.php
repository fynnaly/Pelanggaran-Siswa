<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Jurusan {{ $department->code }}</h1></div>
            <div style="display:flex;gap:var(--sp-sm)">
                @if(auth()->user()?->hasAnyRole(['admin','kesiswaan','kepala_sekolah']))
                    <a href="{{ route('jurusan.edit', $department) }}" class="btn btn-secondary"><i data-lucide="pencil" class="icon-sm"></i> Edit</a>
                @endif
                <a href="{{ route('jurusan.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif

        <div class="card" style="padding:var(--sp-lg);margin-bottom:var(--sp-lg)">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--sp-md)">
                <div><p class="text-sm text-muted">Kode</p><p><strong>{{ $department->code }}</strong></p></div>
                <div><p class="text-sm text-muted">Nama</p><p>{{ $department->name }}</p></div>
                <div><p class="text-sm text-muted">Status</p><p><span class="badge {{ $department->status === 'active' ? 'badge-success' : '' }}">{{ $department->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></p></div>
                <div><p class="text-sm text-muted">Jumlah Kelas</p><p>{{ $department->classes->count() }}</p></div>
            </div>
            @if($department->description)
                <div style="margin-top:var(--sp-md)"><p class="text-sm text-muted">Deskripsi</p><p>{{ $department->description }}</p></div>
            @endif
        </div>

        <h2 style="font-size:16px;margin-bottom:var(--sp-sm)">Kelas pada Jurusan Ini</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Kelas</th><th>Tahun Ajaran</th><th>Wali Kelas</th></tr></thead>
                <tbody>
                    @forelse($department->classes as $class)
                        <tr>
                            <td><strong>{{ $class->name }}</strong></td>
                            <td class="text-sm text-muted">{{ $class->academicYear?->name ?? '-' }}</td>
                            <td class="text-sm text-muted">{{ $class->homeroomTeacher?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada kelas pada jurusan ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
