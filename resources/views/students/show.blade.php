<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>{{ $student->full_name }}</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('students.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
                <a href="{{ route('students.edit', $student) }}" class="btn btn-primary btn-sm"><i data-lucide="pencil" class="icon-sm"></i> Edit</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card">
            <h2 style="font-size:1.125rem;font-weight:700;margin-bottom:var(--sp-md)">Informasi Siswa</h2>
            <table style="width:100%">
                <tbody>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">NISN</td><td style="font-weight:600">{{ $student->nisn }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">NIS</td><td style="font-weight:600">{{ $student->nis }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Jenis Kelamin</td><td>{{ $student->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Tanggal Lahir</td><td>{{ $student->birth_date?->format('d/m/Y') ?? '-' }}</td></tr>
                    @php
                        $enrollments = $student->enrollments->sortByDesc('enrolled_at');
                        $latestEnr = $enrollments->first();
                    @endphp
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Kelas</td><td>{{ $latestEnr?->schoolClass?->name ?? '-' }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Tahun Ajaran</td><td>{{ $latestEnr?->academicYear?->name ?? '-' }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Telepon</td><td>{{ $student->phone ?? '-' }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Alamat</td><td>{{ $student->address ?? '-' }}</td></tr>
                    @if(filled($student->parent_name))
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Nama Orang Tua</td><td>{{ $student->parent_name }}</td></tr>
                    @endif
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">No. HP Orang Tua</td><td>{{ $student->parent_phone ?? '-' }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Status</td><td><span class="badge {{ $student->status==='active'?'badge-success':'' }}">{{ ['active'=>'Aktif','graduated'=>'Lulus','inactive'=>'Tidak Aktif','transferred'=>'Pindah'][$student->status] ?? ucfirst($student->status) }}</span></td></tr>
                </tbody>
            </table>
        </div>

        <div class="card" style="margin-top:var(--sp-lg)">
            <h2 style="font-size:1.125rem;font-weight:700;margin-bottom:var(--sp-md)">Riwayat Enrollment</h2>
            @if($student->enrollments->isEmpty())
                <p style="color:var(--on-surface-muted)">Belum ada riwayat enrollment.</p>
            @else
                <table style="width:100%">
                    <thead>
                        <tr style="text-align:left;color:var(--on-surface-muted);border-bottom:1px solid var(--border)">
                            <th style="padding:8px 0">Tahun Ajaran</th>
                            <th style="padding:8px 0">Kelas</th>
                            <th style="padding:8px 0">Status</th>
                            <th style="padding:8px 0">Mulai</th>
                            <th style="padding:8px 0">Selesai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollments as $enr)
                        <tr style="border-bottom:1px solid var(--border)">
                            <td style="padding:8px 0">{{ $enr->academicYear?->name ?? '-' }}</td>
                            <td style="padding:8px 0">{{ $enr->schoolClass?->name ?? '-' }}</td>
                            <td style="padding:8px 0"><span class="badge {{ $enr->status==='active'?'badge-success':'' }}">{{ ['active'=>'Aktif','graduated'=>'Lulus','inactive'=>'Tidak Aktif','transferred'=>'Pindah','completed'=>'Selesai','withdrawn'=>'Ditarik'][$enr->status] ?? ucfirst($enr->status) }}</span></td>
                            <td style="padding:8px 0">{{ $enr->enrolled_at?->format('d/m/Y') ?? '-' }}</td>
                            <td style="padding:8px 0">{{ $enr->ended_at?->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
