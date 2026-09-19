<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Kasus / Detail</div>
        <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-sm)">
            <div><h1>Detail Kasus</h1></div>
            <a href="{{ route('discipline-cases.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
        @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif

        <div class="card" style="margin-bottom:var(--sp-lg)">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--sp-lg)">
                <div>
                    <div class="label">Nomor Kasus</div>
                    <div style="margin-top:var(--sp-xs);font-weight:600;font-family:ui-monospace,monospace;font-size:.875rem">{{ $disciplineCase->case_number }}</div>
                </div>
                <div>
                    <div class="label">Siswa</div>
                    <div style="margin-top:var(--sp-xs);font-weight:600">{{ $disciplineCase->student?->full_name ?? '-' }}</div>
                    <div class="text-sm text-muted">{{ $disciplineCase->student?->schoolClass?->name ?? '' }}</div>
                </div>
                <div>
                    <div class="label">Pelanggaran</div>
                    <div style="margin-top:var(--sp-xs)">{{ $disciplineCase->violationCategory?->name ?? '-' }}</div>
                </div>
                <div>
                    <div class="label">Poin</div>
                    <div style="margin-top:var(--sp-xs);font-weight:700;color:var(--danger)">-{{ $disciplineCase->violationCategory?->points ?? 0 }}</div>
                </div>
                <div>
                    <div class="label">Status</div>
                    <div style="margin-top:var(--sp-xs)">
                        @if($disciplineCase->status==='found')<span class="badge badge-warning">Diproses</span>
                        @elseif($disciplineCase->status==='validated')<span class="badge" style="background:var(--ws);color:var(--wt)">Divalidasi</span>
                        @elseif($disciplineCase->status==='done')<span class="badge badge-success">Selesai</span>
                        @else<span class="badge" style="background:var(--ds);color:var(--dt)">{{ ucfirst($disciplineCase->status) }}</span>@endif
                    </div>
                </div>
                <div>
                    <div class="label">Lokasi</div>
                    <div style="margin-top:var(--sp-xs)">{{ $disciplineCase->location ?? '-' }}</div>
                </div>
            </div>

            <div style="margin-top:var(--sp-lg);border-top:1px solid var(--border);padding-top:var(--sp-lg)">
                <div class="label">Deskripsi</div>
                <div style="margin-top:var(--sp-xs);padding:var(--sp-md);background:var(--surface);border-radius:var(--r-sm);white-space:pre-wrap">{{ $disciplineCase->description ?? '-' }}</div>
            </div>

            <div style="margin-top:var(--sp-lg);border-top:1px solid var(--border);padding-top:var(--sp-lg);display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--sp-lg)">
                <div>
                    <div class="label">Dilaporkan Oleh</div>
                    <div style="margin-top:var(--sp-xs)">{{ $disciplineCase->reporter?->name ?? '-' }}</div>
                </div>
                <div>
                    <div class="label">Divalidasi Oleh</div>
                    <div style="margin-top:var(--sp-xs)">{{ $disciplineCase->validator?->name ?? '-' }}</div>
                </div>
                <div>
                    <div class="label">Waktu Lapor</div>
                    <div style="margin-top:var(--sp-xs)">{{ $disciplineCase->created_at?->format('d/m/Y H:i') }}</div>
                </div>
                <div>
                    <div class="label">Waktu Validasi</div>
                    <div style="margin-top:var(--sp-xs)">{{ $disciplineCase->validated_at?->format('d/m/Y H:i') ?? '-' }}</div>
                </div>
            </div>
        </div>

        @if($disciplineCase->status === 'found' || $disciplineCase->status === 'validated')
            <div class="card" style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;justify-content:flex-end">
                @if($disciplineCase->status === 'found')
                    <form action="{{ route('discipline-cases.validate', $disciplineCase) }}" method="POST" onsubmit="return confirm('Validasi kasus ini?')">@csrf @method('PATCH')<input type="hidden" name="validation_passed" value="1"><button class="btn btn-primary">Validasi</button></form>
                    <form action="{{ route('discipline-cases.validate', $disciplineCase) }}" method="POST" onsubmit="return confirm('Tolak kasus ini?')">@csrf @method('PATCH')<input type="hidden" name="validation_passed" value="0"><button class="btn btn-secondary">Tolak</button></form>
                @endif
                @if($disciplineCase->status === 'validated')
                    <form action="{{ route('discipline-cases.done', $disciplineCase) }}" method="POST" onsubmit="return confirm('Tandai kasus selesai?')">@csrf @method('PATCH')<button class="btn btn-primary">Selesai</button></form>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>