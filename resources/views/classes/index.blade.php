<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Daftar Kelas</h1><p class="text-muted text-sm" data-live-count>{{ $classes->total() }} kelas</p></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('classes.create') }}" class="btn btn-primary"><i data-lucide="plus" class="icon-sm"></i> Tambah</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
        <div class="toolbar" style="margin-bottom:var(--sp-md)">
            <form action="{{ route('classes.index') }}" method="GET" data-live="#classRegion" id="classFilter" style="display:flex;gap:var(--sp-sm);flex:1;align-items:stretch">
                <select name="year_id" class="input" style="width:auto;min-width:130px;flex-shrink:0" onchange="this.form.submit()">
                    @foreach($academicYears as $yr)
                        <option value="{{ $yr->id }}" {{ $selectedYearId==$yr->id?'selected':'' }}>{{ $yr->name }}{{ $yr->is_active?' (Aktif)':'' }}</option>
                    @endforeach
                </select>
                <a href="{{ route('tahun-ajaran.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="calendar" class="icon-sm"></i> Kelola Tahun Ajaran</a>
            </form>
        </div>
        <div id="classRegion">
        <div class="table-wrap">
            <table>
                <thead><tr><th style="width:48px"><x-sortable-header column="id" label="No" :sort="$sort" :order="$order" route="classes.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="name" label="Nama Kelas" :sort="$sort" :order="$order" route="classes.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="grade_level" label="Tingkat" :sort="$sort" :order="$order" route="classes.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="major" label="Program Keahlian" :sort="$sort" :order="$order" route="classes.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="status" label="Status" :sort="$sort" :order="$order" route="classes.index" :params="request()->except('sort','order','page')" /></th><th class="mono" style="width:96px;text-align:center;white-space:nowrap;font-variant-numeric:tabular-nums"><x-sortable-header column="students_count" label="Jumlah Siswa" :sort="$sort" :order="$order" route="classes.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="homeroom_teacher_id" label="Wali Kelas" :sort="$sort" :order="$order" route="classes.index" :params="request()->except('sort','order','page')" /></th><th style="width:100px">Aksi</th></tr></thead>
                <tbody>
                    @forelse($classes as $class)
                        <tr>
                            <td class="text-sm text-muted">{{ $class->id }}</td>
                            <td style="font-weight:600">{{ $class->name }}</td>
                            <td class="mono" style="font-weight:600">{{ $class->tingkat ?? '-' }}</td>
                            <td class="text-sm text-muted">{{ $class->major ?? '-' }}</td>
                            <td><span class="badge {{ ($class->status ?? 'active')==='active'?'badge-success':'' }}" style="{{ ($class->status ?? 'active')!=='active'?'background:var(--surface);color:var(--on-surface-muted)':'' }}">{{ ($class->status ?? 'active')==='active'?'Aktif':'Nonaktif' }}</span></td>
                            <td class="mono" style="width:96px;text-align:center;white-space:nowrap;font-variant-numeric:tabular-nums">{{ $class->students_count }}</td>
                            <td>
                                <form action="{{ route('classes.update', $class) }}" method="POST" class="inline" style="display:inline">
                                    @csrf @method('PUT')
                                    <select name="homeroom_teacher_id" class="input" style="width:auto;padding:8px 32px 8px 10px;font-size:.8125rem" onchange="this.form.submit()"
                                    >
                                        <option value="">-</option>
                                        @foreach($teachers as $t)
                                            <option value="{{ $t->id }}" {{ old('homeroom_teacher_id', $class->homeroom_teacher_id)==$t->id?'selected':'' }}>{{ $t->name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td><div class="action-cell">
                                <a href="{{ route('classes.show', $class) }}" class="action-btn" title="Detail"><i data-lucide="eye" style="width:14px;height:14px"></i></a>
                                <a href="{{ route('classes.edit', $class) }}" class="action-btn" title="Edit"><i data-lucide="pencil" style="width:14px;height:14px"></i></a>
                                <form action="{{ route('classes.destroy', $class) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kelas {{ $class->name }}?')">@csrf @method('DELETE')<button class="action-btn" title="Hapus" style="color:var(--danger)"><i data-lucide="trash-2" style="width:14px;height:14px"></i></button></form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada kelas. Tambah dulu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display:flex;justify-content:center;margin-top:var(--sp-lg)"><div class="pagination" style="margin-top:0">{{ $classes->links() }}</div></div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
