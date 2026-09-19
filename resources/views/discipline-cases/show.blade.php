<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Kasus / Detail</div>
        <div class="page-header">
            <div><h1 style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="file-text" class="icon"></i> {{ $case->case_number }}</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                @if($case->status==='found')
                    <form action="{{ route('discipline-cases.validate', $case) }}" method="POST" onsubmit="return confirm('Validasi kasus ini?')">@csrf @method('PATCH')<input type="hidden" name="validation_passed" value="1"><button class="btn btn-primary btn-sm"><i data-lucide="check" class="icon-sm"></i> Validasi</button></form>
                @endif
                @if($case->status==='validated')
                    <form action="{{ route('discipline-cases.done', $case) }}" method="POST" onsubmit="return confirm('Tandai selesai?')">@csrf @method('PATCH')<button class="btn btn-primary btn-sm"><i data-lucide="check-circle" class="icon-sm"></i> Selesai</button></form>
                @endif
                <a href="{{ route('discipline-cases.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="layout-2col" style="align-items:start">
            <div>
                <div class="card" style="margin-bottom:var(--sp-lg)">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="info" class="icon"></i> Detail Kasus</h2>
                    <table style="width:100%;border-collapse:collapse">
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted);width:160px">Nomor Kasus</td><td class="mono" style="padding:8px 0;font-weight:600">{{ $case->case_number }}</td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Status</td><td style="padding:8px 0">
                            @if($case->status==='found')<span class="badge badge-warning"><i data-lucide="clock" style="width:12px;height:12px"></i> Diproses</span>
                            @elseif($case->status==='validated')<span class="badge" style="background:var(--ws);color:var(--wt)"><i data-lucide="check" style="width:12px;height:12px"></i> Divalidasi</span>
                            @elseif($case->status==='done')<span class="badge badge-success"><i data-lucide="check-circle" style="width:12px;height:12px"></i> Selesai</span>
                            @else<span class="badge badge-danger"><i data-lucide="x-circle" style="width:12px;height:12px"></i> {{ ucfirst($case->status) }}</span>@endif
                        </td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Tanggal Dilaporkan</td><td style="padding:8px 0">{{ $case->created_at?->format('d M Y H:i') }}</td></tr>
                        @if($case->validated_at)<tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Divalidasi</td><td style="padding:8px 0">{{ $case->validated_at?->format('d M Y H:i') }}</td></tr>@endif
                        @if($case->resolved_at)<tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Selesai</td><td style="padding:8px 0">{{ $case->resolved_at?->format('d M Y H:i') }}</td></tr>@endif
                    </table>
                </div>
                <div class="card">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="file-text" class="icon"></i> Kronologi / Catatan</h2>
                    @if($case->notes)<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r-sm);padding:var(--sp-md);line-height:1.7">{{ nl2br(e($case->notes)) }}</div>@else<p class="text-muted text-sm">Tidak ada catatan.</p>@endif
                </div>
            </div>
            <div>
                <div class="card" style="margin-bottom:var(--sp-lg)">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="user" class="icon"></i> Siswa</h2>
                    <table style="width:100%;border-collapse:collapse">
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted);width:140px">Nama</td><td style="padding:8px 0;font-weight:600">{{ $case->student?->full_name }}</td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">NIS</td><td class="mono" style="padding:8px 0">{{ $case->student?->nis }}</td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Kelas</td><td style="padding:8px 0">{{ $case->student?->schoolClass?->name ?? '-' }}</td></tr>
                    </table>
                </div>
                <div class="card" style="margin-bottom:var(--sp-lg)">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="tag" class="icon"></i> Pelanggaran</h2>
                    <table style="width:100%;border-collapse:collapse">
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted);width:140px">Kategori</td><td style="padding:8px 0;font-weight:600">{{ $case->violationCategory?->name }}</td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Tingkat</td><td style="padding:8px 0"><span class="badge badge-warning">{{ ucfirst($case->violationCategory?->severity ?? '-') }}</span></td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Poin Dikurangi</td><td class="mono" style="padding:8px 0;font-size:1.25rem;font-weight:700;color:var(--danger)">-{{ $case->violationCategory?->points }}</td></tr>
                    </table>
                </div>
                <div class="card">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="user-check" class="icon"></i> Pelapor</h2>
                    <table style="width:100%;border-collapse:collapse">
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted);width:140px">Dilaporkan oleh</td><td style="padding:8px 0;font-weight:600">{{ $case->reporter?->name }}</td></tr>
                        @if($case->validator)<tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Divalidasi oleh</td><td style="padding:8px 0;font-weight:600">{{ $case->validator?->name }}</td></tr>@endif
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>