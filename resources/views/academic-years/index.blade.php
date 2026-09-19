<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Tahun Ajaran</div>
        <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-sm)">
            <div>
                <h1>Tahun Ajaran</h1>
                <p class="text-muted text-sm">{{ $academicYears->total() }} tahun ajaran</p>
            </div>
            <a href="{{ route('academic-years.create') }}" class="btn btn-primary">Tambah Tahun Ajaran</a>
        </div>
    </x-slot>

    <div class="main-wrap">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tahun Ajaran</th>
                        <th>Tanggal Mulai</th>
                        <th>Status</th>
                        <th style="width:120px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($academicYears as $year)
                        <tr>
                            <td><strong>{{ $year->name }}</strong></td>
                            <td class="text-sm text-muted">{{ $year->start_date?->format('d/m/Y') ?? '-' }}</td>
                            <td>
                                @if($year->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge" style="background:var(--surface);color:var(--on-surface-muted)">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-cell">
                                    <a href="{{ route('academic-years.edit', $year) }}" class="action-btn" title="Edit">E</a>
                                    <form action="{{ route('academic-years.destroy', $year) }}" method="POST" class="inline" onsubmit="return confirm('Hapus tahun ajaran {{ $year->name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="action-btn" title="Hapus" style="color:var(--danger)">X</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="padding:40px;text-align:center;color:var(--on-surface-muted)">Belum ada data tahun ajaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($academicYears->hasPages())
            <div style="display:flex;justify-content:flex-end;margin-top:var(--sp-lg)">
                <div class="pagination" style="margin-top:0">{{ $academicYears->links() }}</div>
            </div>
        @endif
    </div>
</x-app-layout>
