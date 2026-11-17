<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div style="display:flex;align-items:center;gap:var(--sp-md);min-width:0">
                <img src="{{ $student->avatar_url }}" alt="Foto {{ $student->full_name }}"
                     style="width:64px;height:64px;border-radius:999px;object-fit:cover;flex-shrink:0;border:1px solid var(--border);background:var(--surface)">
                <div style="min-width:0">
                    <h1 style="margin:0">{{ $student->full_name }}</h1>
                    <p class="text-muted text-sm" style="margin:4px 0 0">
                        {{ $student->nis ? 'NIS '.$student->nis : 'NIS belum diisi' }}
                        @if($student->nisn) · NISN {{ $student->nisn }} @endif
                    </p>
                </div>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('students.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
                <a href="{{ route('students.edit', $student) }}" class="btn btn-primary btn-sm"><i data-lucide="pencil" class="icon-sm"></i> Edit</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card">
            <h2 style="font-size:1.125rem;font-weight:700;margin-bottom:var(--sp-md)">Informasi Siswa</h2>
            @php
                $enrollments = $student->enrollments->sortByDesc('enrolled_at');
                $latestEnr = $enrollments->first();
            @endphp
            <div class="table-wrap table-wrap--plain">
            <table class="detail-table">
                <tbody>
                    <tr><td class="dl">NISN</td><td style="font-weight:600">{{ $student->nisn }}</td></tr>
                    <tr><td class="dl">NIS</td><td style="font-weight:600">{{ $student->nis }}</td></tr>
                    <tr><td class="dl">Jenis Kelamin</td><td>{{ $student->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td></tr>
                    <tr><td class="dl">Tanggal Lahir</td><td>{{ $student->birth_date?->format('d/m/Y') ?? '-' }}</td></tr>
                    <tr><td class="dl">Kelas</td><td>{{ $latestEnr?->schoolClass?->name ?? '-' }}</td></tr>
                    <tr><td class="dl">Tahun Ajaran</td><td>{{ $latestEnr?->academicYear?->name ?? '-' }}</td></tr>
                    <tr><td class="dl">Telepon</td><td>{{ $student->phone ?? '-' }}</td></tr>
                    <tr><td class="dl">Alamat</td><td>{{ $student->address ?? '-' }}</td></tr>
                    @if(filled($student->parent_name))
                    <tr><td class="dl">Nama Orang Tua</td><td>{{ $student->parent_name }}</td></tr>
                    @endif
                    <tr><td class="dl">No. HP Orang Tua</td><td>{{ $student->parent_phone ?? '-' }}</td></tr>
                    <tr><td class="dl">Status</td><td><span class="badge {{ $student->status==='active'?'badge-success':'' }}">{{ ['active'=>'Aktif','graduated'=>'Lulus','inactive'=>'Tidak Aktif','transferred'=>'Pindah'][$student->status] ?? ucfirst($student->status) }}</span></td></tr>
                </tbody>
            </table>
            </div>
        </div>

        <div class="card">
            <h2 style="font-size:1.125rem;font-weight:700;margin-bottom:var(--sp-md)">Riwayat Kelas</h2>
            @if($student->enrollments->isEmpty())
                <p style="color:var(--on-surface-muted)">Belum ada riwayat kelas.</p>
            @else
                <div class="table-wrap table-wrap--plain">
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th>Tahun Ajaran</th>
                            <th>Kelas</th>
                            <th>Status</th>
                            <th>Mulai</th>
                            <th>Selesai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollments as $enr)
                        <tr>
                            <td>{{ $enr->academicYear?->name ?? '-' }}</td>
                            <td>{{ $enr->schoolClass?->name ?? '-' }}</td>
                            <td><span class="badge {{ $enr->status==='active'?'badge-success':'' }}">{{ ['active'=>'Aktif','graduated'=>'Lulus','inactive'=>'Tidak Aktif','transferred'=>'Pindah','completed'=>'Selesai','withdrawn'=>'Ditarik'][$enr->status] ?? ucfirst($enr->status) }}</span></td>
                            <td>{{ $enr->enrolled_at?->format('d/m/Y') ?? '-' }}</td>
                            <td>{{ $enr->ended_at?->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @endif
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
