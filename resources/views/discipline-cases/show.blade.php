<x-app-layout>
    <x-slot name="header">
        
        <div class="page-header">
            <div><h1 style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="file-text" class="icon"></i> {{ $disciplineCase->case_number }}</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;align-items:center">
                @if($disciplineCase->status==='found')
                    <form action="{{ route('kasus-pelanggaran.validate', $disciplineCase) }}" method="POST" onsubmit="return confirm('Validasi kasus ini?')" style="margin:0;padding:0;border:none;display:inline"><input type="hidden" name="_token" value="{{ csrf_token() }}"> @method('PATCH')<input type="hidden" name="validation_passed" value="1"><button class="btn btn-primary btn-sm"><i data-lucide="check" class="icon-sm"></i> Validasi</button></form>
                    <form action="{{ route('kasus-pelanggaran.dismiss', $disciplineCase) }}" method="POST" onsubmit="return confirm('Buang kasus ini? Poin tidak akan dikurangi.')" style="margin:0;padding:0;border:none;display:inline">@csrf @method('PATCH')<button class="btn btn-danger btn-sm"><i data-lucide="x" class="icon-sm"></i> Buang</button></form>
                @endif
                @if($disciplineCase->status==='validated')
                    <form action="{{ route('kasus-pelanggaran.done', $disciplineCase) }}" method="POST" onsubmit="return confirm('Tandai selesai?')" style="margin:0;padding:0;border:none;display:inline">@csrf @method('PATCH')<button class="btn btn-primary btn-sm"><i data-lucide="check-circle" class="icon-sm"></i> Selesai</button></form>
                @endif
                <a href="{{ route('kasus-pelanggaran.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="layout-2col" style="align-items:start">
            <div>
                <div class="card" style="margin-bottom:var(--sp-lg)">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="info" class="icon"></i> Detail Kasus</h2>
                    <table style="width:100%;border-collapse:collapse">
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted);width:160px">Nomor Kasus</td><td class="mono" style="padding:8px 0;font-weight:600">{{ $disciplineCase->case_number }}</td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Status</td><td style="padding:8px 0">
                            @if($disciplineCase->status==='found')<span class="badge badge-warning"><i data-lucide="clock" style="width:12px;height:12px"></i> Diproses</span>
                            @elseif($disciplineCase->status==='validated')<span class="badge" style="background:var(--ws);color:var(--wt)"><i data-lucide="check" style="width:12px;height:12px"></i> Divalidasi</span>
                            @elseif($disciplineCase->status==='done')<span class="badge badge-success"><i data-lucide="check-circle" style="width:12px;height:12px"></i> Selesai</span>
                            @else<span class="badge badge-danger"><i data-lucide="x-circle" style="width:12px;height:12px"></i> {{ ucfirst($disciplineCase->status) }}</span>@endif
                        </td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Tanggal Dilaporkan</td><td style="padding:8px 0">{{ $disciplineCase->created_at?->format('d M Y H:i') }}</td></tr>
                        @if($disciplineCase->validated_at)<tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Divalidasi</td><td style="padding:8px 0">{{ $disciplineCase->validated_at?->format('d M Y H:i') }}</td></tr>@endif
                        @if($disciplineCase->resolved_at)<tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Selesai</td><td style="padding:8px 0">{{ $disciplineCase->resolved_at?->format('d M Y H:i') }}</td></tr>@endif
                    </table>
                </div>
                <div class="card">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="file-text" class="icon"></i> Kronologi / Catatan</h2>
                    @if($disciplineCase->description)<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r-sm);padding:var(--sp-md);line-height:1.7">{{ nl2br(e($disciplineCase->description)) }}</div>@else<p class="text-muted text-sm">Tidak ada catatan.</p>@endif
                </div>
            </div>
            <div>
                <div class="card" style="margin-bottom:var(--sp-lg)">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="user" class="icon"></i> Siswa</h2>
                    <table style="width:100%;border-collapse:collapse">
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted);width:140px">Nama</td><td style="padding:8px 0;font-weight:600">{{ $disciplineCase->student?->full_name }}</td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">NIS</td><td class="mono" style="padding:8px 0">{{ $disciplineCase->student?->nis }}</td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Kelas</td><td style="padding:8px 0">{{ $disciplineCase->student?->schoolClass?->name ?? '-' }}</td></tr>
                    </table>
                </div>
                <div class="card" style="margin-bottom:var(--sp-lg)">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="tag" class="icon"></i> Pelanggaran</h2>
                    <table style="width:100%;border-collapse:collapse">
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted);width:140px">Kategori</td><td style="padding:8px 0;font-weight:600">{{ $disciplineCase->violationCategory?->name }}</td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Tingkat</td><td style="padding:8px 0"><span class="badge badge-warning">{{ ucfirst($disciplineCase->violationCategory?->severity ?? '-') }}</span></td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Poin Dikurangi</td><td class="mono" style="padding:8px 0;font-size:1.25rem;font-weight:700;color:var(--danger)">-{{ $disciplineCase->violationCategory?->points }}</td></tr>
                    </table>
                </div>
                <div class="card">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="user-check" class="icon"></i> Pelapor</h2>
                    <table style="width:100%;border-collapse:collapse">
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted);width:140px">Dilaporkan oleh</td><td style="padding:8px 0;font-weight:600">{{ $disciplineCase->reporter?->name }}</td></tr>
                        @if($disciplineCase->validator)<tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Divalidasi oleh</td><td style="padding:8px 0;font-weight:600">{{ $disciplineCase->validator?->name }}</td></tr>@endif
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>