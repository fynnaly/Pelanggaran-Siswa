<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1 style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="award" class="icon"></i> Rekam Prestasi</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                @if($achievementRecord->status==='pending')
                    <form action="{{ route('achievement-records.validate', $achievementRecord) }}" method="POST" onsubmit="return confirm('Validasi prestasi ini? Poin akan ditambahkan.')">@csrf @method('PATCH')<button class="btn btn-primary btn-sm"><i data-lucide="check" class="icon-sm"></i> Validasi</button></form>
                    <form action="{{ route('achievement-records.dismiss', $achievementRecord) }}" method="POST" onsubmit="return confirm('Tolak rekam prestasi ini?')">@csrf @method('PATCH')<button class="btn btn-danger btn-sm"><i data-lucide="x" class="icon-sm"></i> Tolak</button></form>
                @endif
                <a href="{{ route('achievement-records.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="layout-2col" style="align-items:start">
            <div>
                <div class="card" style="margin-bottom:var(--sp-lg)">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="info" class="icon"></i> Detail Prestasi</h2>
                    <table style="width:100%;border-collapse:collapse">
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted);width:160px">Status</td><td style="padding:8px 0">
                            @if($achievementRecord->status==='pending')<span class="badge badge-warning"><i data-lucide="clock" style="width:12px;height:12px"></i> Pending</span>
                            @elseif($achievementRecord->status==='verified')<span class="badge badge-success"><i data-lucide="check-circle" style="width:12px;height:12px"></i> Divalidasi</span>
                            @else<span class="badge badge-danger"><i data-lucide="x-circle" style="width:12px;height:12px"></i> Ditolak</span>@endif
                        </td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Tanggal Dicatat</td><td style="padding:8px 0">{{ $achievementRecord->created_at?->format('d M Y H:i') }}</td></tr>
                        @if($achievementRecord->verified_at)<tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Divalidasi</td><td style="padding:8px 0">{{ $achievementRecord->verified_at?->format('d M Y H:i') }}</td></tr>@endif
                    </table>
                </div>
                <div class="card">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="file-text" class="icon"></i> Keterangan</h2>
                    @if($achievementRecord->description)<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r-sm);padding:var(--sp-md);line-height:1.7">{{ nl2br(e($achievementRecord->description)) }}</div>@else<p class="text-muted text-sm">Tidak ada keterangan.</p>@endif
                </div>
            </div>
            <div>
                <div class="card" style="margin-bottom:var(--sp-lg)">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="user" class="icon"></i> Siswa</h2>
                    <table style="width:100%;border-collapse:collapse">
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted);width:140px">Nama</td><td style="padding:8px 0;font-weight:600">{{ $achievementRecord->student?->full_name }}</td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">NIS</td><td class="mono" style="padding:8px 0">{{ $achievementRecord->student?->nis }}</td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Kelas</td><td style="padding:8px 0">{{ $achievementRecord->student?->schoolClass?->name ?? '-' }}</td></tr>
                    </table>
                </div>
                <div class="card" style="margin-bottom:var(--sp-lg)">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="trophy" class="icon"></i> Kategori Prestasi</h2>
                    <table style="width:100%;border-collapse:collapse">
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted);width:140px">Kategori</td><td style="padding:8px 0;font-weight:600">{{ $achievementRecord->achievementCategory?->name }}</td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Kode</td><td class="mono" style="padding:8px 0">{{ $achievementRecord->achievementCategory?->code }}</td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Poin Ditambahkan</td><td class="mono" style="padding:8px 0;font-size:1.25rem;font-weight:700;color:var(--tertiary)">+{{ $achievementRecord->achievementCategory?->points }}</td></tr>
                    </table>
                </div>
                <div class="card">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="user-check" class="icon"></i> Pencatat</h2>
                    <table style="width:100%;border-collapse:collapse">
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted);width:140px">Dicatat oleh</td><td style="padding:8px 0;font-weight:600">{{ $achievementRecord->recorder?->name }}</td></tr>
                        @if($achievementRecord->verifier)<tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Divalidasi oleh</td><td style="padding:8px 0;font-weight:600">{{ $achievementRecord->verifier?->name }}</td></tr>@endif
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
