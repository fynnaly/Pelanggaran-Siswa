<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><div class="breadcrumb">Beranda / Tahun Ajaran</div><h1>Tahun Ajaran</h1><p class="text-muted text-sm">{{ $academicYears->total() }} tahun ajaran</p></div>
            <a href="{{ route('academic-years.create') }}" class="btn btn-primary"><i data-lucide="plus" class="icon-sm"></i> Tambah</a>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tahun Ajaran</th><th>Tanggal Mulai</th><th>Status</th><th style="width:100px">Aksi</th></tr></thead>
                <tbody>
                    @forelse($academicYears as $year)
                        <tr>
                            <td><strong>{{ $year->name }}</strong></td>
                            <td class="text-sm text-muted">{{ $year->start_date?->format('d/m/Y') ?? '-' }}</td>
                            <td><span class="badge {{ $year->is_active?'badge-success':'' }}" style="{{ !$year->is_active?'background:var(--surface);color:var(--on-surface-muted)':'' }}">{{ $year->is_active?'Aktif':'Nonaktif' }}</span></td>
                            <td><div class="action-cell">
                                <a href="{{ route('academic-years.edit', $year) }}" class="action-btn" title="Edit"><i data-lucide="pencil" style="width:14px;height:14px"></i></a>
                                <form action="{{ route('academic-years.destroy', $year) }}" method="POST" class="inline" onsubmit="return confirm('Hapus {{ $year->name }}?')">@csrf @method('DELETE')<button class="action-btn" title="Hapus" style="color:var(--danger)"><i data-lucide="trash-2" style="width:14px;height:14px"></i></button></form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($academicYears->hasPages())<div style="display:flex;justify-content:flex-end;margin-top:var(--sp-lg)"><div class="pagination" style="margin-top:0">{{ $academicYears->links() }}</div></div>@endif
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
