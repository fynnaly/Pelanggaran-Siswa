<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>{{ $user->name }}</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
                <a href="{{ route('users.edit', $user) }}" class="btn btn-primary btn-sm"><i data-lucide="pencil" class="icon-sm"></i> Edit</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card">
            <h2 style="font-size:1.125rem;font-weight:700;margin-bottom:var(--sp-md)">Informasi Pengguna</h2>
            <table style="width:100%">
                <tbody>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Nama</td><td style="font-weight:600">{{ $user->name }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Email</td><td>{{ $user->email }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Role / Jabatan</td>
                        <td>
                            <div style="display:flex;gap:4px;flex-wrap:wrap">
                                @forelse($user->roles as $r)
                                    <span class="badge" style="
                                        @if($r->name==='siswa') background:var(--bs);color:var(--bt)
                                        @elseif(in_array($r->name,['admin','kepala_sekolah'])) background:var(--ds);color:var(--dt)
                                        @else background:var(--ws);color:var(--wt) @endif
                                    ">{{ $r->name }}</span>
                                @empty
                                    <span class="text-muted">-</span>
                                @endforelse
                            </div>
                        </td>
                    </tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Terdaftar</td><td>{{ $user->created_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
                </tbody>
            </table>
        </div>

        @if($user->student)
        <div class="card" style="margin-top:var(--sp-lg)">
            <h2 style="font-size:1.125rem;font-weight:700;margin-bottom:var(--sp-md)">Identitas Siswa</h2>
            <table style="width:100%">
                <tbody>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">NISN</td><td style="font-weight:600">{{ $user->student->nisn }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">NIS</td><td>{{ $user->student->nis }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Nama Siswa</td><td>{{ $user->student->full_name }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Kelas</td><td>{{ $user->student->currentEnrollment?->schoolClass?->name ?? '-' }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Tahun Ajaran</td><td>{{ $user->student->currentEnrollment?->academicYear?->name ?? '-' }}</td></tr>
                    <tr><td style="padding:8px 0;color:var(--on-surface-muted)">Status</td><td><span class="badge {{ $user->student->status==='active'?'badge-success':'' }}">{{ ucfirst($user->student->status) }}</span></td></tr>
                </tbody>
            </table>
            <a href="{{ route('students.show', $user->student) }}" class="btn btn-secondary btn-sm" style="margin-top:var(--sp-md)"><i data-lucide="eye" class="icon-sm"></i> Lihat Detail Siswa</a>
        </div>
        @endif
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
