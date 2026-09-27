<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>{{ $class->name }}</h1>
                <p class="text-muted text-sm">{{ $class->academicYear?->name ?? '-' }} &middot; {{ $class->grade_level }} {{ $class->major }}</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('classes.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
                <a href="{{ route('classes.edit', $class) }}" class="btn btn-primary btn-sm"><i data-lucide="pencil" class="icon-sm"></i> Edit</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card">
            <h2 style="font-size:1.125rem;font-weight:700;margin-bottom:var(--sp-md)">Informasi Kelas</h2>
            <table style="width:100%">
                <tbody>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted);width:180px">Nama Kelas</td><td style="font-weight:600">{{ $class->name }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Tingkat</td><td class="mono" style="font-weight:600">{{ $class->grade_level ?? '-' }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Jurusan</td><td>{{ $class->major ?? '-' }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Tahun Ajaran</td><td>{{ $class->academicYear?->name ?? '-' }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Guru</td><td>{{ $class->homeroomTeacher?->name ?? '-' }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Guru 2</td><td>{{ $class->assistantTeacher?->name ?? '-' }}</td></tr>
                    @if($class->capacity)
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Kapasitas</td><td>{{ $students->total() }} / {{ $class->capacity }}</td></tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="card" style="margin-top:var(--sp-lg)">
            <h2 style="font-size:1.125rem;font-weight:700;margin-bottom:var(--sp-md)">Jumlah Siswa</h2>
            <div style="display:flex;align-items:baseline;gap:var(--sp-sm);margin-bottom:var(--sp-md)">
                <div class="stat-value" style="margin:0">{{ number_format($students->total()) }}</div>
                <span class="text-muted text-sm">siswa terdaftar di kelas ini</span>
            </div>
            @if($students->total() > 0)
                <div style="display:flex;gap:var(--sp-md);flex-wrap:wrap">
                    @foreach($zoneCounts as $zone => $count)
                        @if($count > 0)
                            <div style="display:flex;align-items:center;gap:var(--sp-xs)">
                                <div style="width:10px;height:10px;border-radius:50%;background:{{ ['Intervensi Khusus'=>'var(--ds)','Pembinaan 4'=>'var(--dt)','Pembinaan 3'=>'var(--bs)','Pembinaan 2'=>'var(--ws)','Pembinaan 1'=>'var(--wt)','Normal'=>'var(--bt)','Luar Biasa'=>'var(--tertiary)','Istimewa'=>'var(--primary)'][$zone] ?? 'var(--border)' }}"></div>
                                <span class="text-sm">{{ $zone }}: {{ $count }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        @if($students->isNotEmpty())
        <div class="card" style="margin-top:var(--sp-lg)">
            <div class="section-header">
                <h2 style="font-size:1.125rem;font-weight:700">Daftar Siswa</h2>
                <a href="{{ route('students.index', ['class_id' => $class->id, 'year_id' => $class->academic_year_id]) }}" class="btn btn-secondary btn-sm"><i data-lucide="users" class="icon-sm"></i> Lihat Semua di Daftar Siswa</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>No</th><th>Nama</th><th>NIS</th><th>Status</th><th>Poin</th></tr></thead>
                    <tbody>
                        @foreach($students as $idx => $student)
                            <tr>
                                <td>{{ $students->firstItem() + $idx }}</td>
                                <td><a href="{{ route('students.show', $student) }}" style="font-weight:600">{{ $student->full_name }}</a></td>
                                <td class="mono">{{ $student->nis }}</td>
                                <td>
                                    @php
                                        $statusMap = ['active'=>'Aktif','graduated'=>'Lulus','inactive'=>'Tidak Aktif','transferred'=>'Pindah'];
                                        $statusBadge = $student->status==='active' ? 'badge-success' : '';
                                    @endphp
                                    <span class="badge {{ $statusBadge }}">{{ $statusMap[$student->status] ?? ucfirst($student->status) }}</span>
                                </td>
                                <td class="mono">{{ $student->pointLedgers->last()->balance_after ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="display:flex;justify-content:flex-end;margin-top:var(--sp-lg)"><div class="pagination" style="margin-top:0">{{ $students->links() }}</div></div>
        </div>
        @endif
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
