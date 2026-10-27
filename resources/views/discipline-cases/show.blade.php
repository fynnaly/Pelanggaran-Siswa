<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1 style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="file-text" class="icon"></i> {{ $disciplineCase->case_number }}</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;align-items:center">
                @can('update', $disciplineCase)
                    @if(in_array($disciplineCase->status, ['found','clarifying','classifying','handling','monitoring'], true))
                    <form action="{{ route('kasus-pelanggaran.update-status', $disciplineCase) }}" method="POST" style="margin:0;padding:0;border:none;display:inline-flex;gap:var(--sp-xs);align-items:center">@csrf @method('PATCH')
                        <select name="status" class="input" style="width:auto;min-width:140px" required>
                            @php $cur = $disciplineCase->status; @endphp
                            @foreach(['handling' => 'Penanganan (Potong Poin)', 'closed' => 'Selesai'] as $val => $label)
                                <option value="{{ $val }}" @selected($val === $cur)>{{ $label }}</option>
                            @endforeach
                            @if($cur === 'found')
                                <option value="found" selected>Temuan (saat ini)</option>
                            @endif
                        </select>
                        <button class="btn btn-primary btn-sm">Ubah Status</button>
                    </form>
                    @endif
                @endcan
                @can('dismiss', $disciplineCase)
                    <form action="{{ route('kasus-pelanggaran.dismiss', $disciplineCase) }}" method="POST" onsubmit="return confirm('Buang kasus ini? Poin tidak akan dikurangi.')" style="margin:0;padding:0;border:none;display:inline">@csrf @method('PATCH')<button class="btn btn-danger btn-sm"><i data-lucide="x" class="icon-sm"></i> Buang</button></form>
                @endcan
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
                            @if($disciplineCase->status==='found')<span class="badge badge-warning"><i data-lucide="clock" style="width:12px;height:12px"></i> Temuan</span>
                            @elseif($disciplineCase->status==='clarifying')<span class="badge badge-warning"><i data-lucide="message-circle" style="width:12px;height:12px"></i> Klarifikasi</span>
                            @elseif($disciplineCase->status==='classifying')<span class="badge" style="background:var(--ws);color:var(--wt)"><i data-lucide="tag" style="width:12px;height:12px"></i> Klasifikasi</span>
                            @elseif($disciplineCase->status==='handling')<span class="badge" style="background:var(--ws);color:var(--wt)"><i data-lucide="gavel" style="width:12px;height:12px"></i> Penanganan</span>
                            @elseif($disciplineCase->status==='monitoring')<span class="badge badge-warning"><i data-lucide="eye" style="width:12px;height:12px"></i> Monitoring</span>
                            @elseif($disciplineCase->status==='closed')<span class="badge badge-success"><i data-lucide="check-circle" style="width:12px;height:12px"></i> Selesai</span>
                            @else<span class="badge badge-danger"><i data-lucide="x-circle" style="width:12px;height:12px"></i> Dibuang</span>@endif
                        </td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Tanggal Dilaporkan</td><td style="padding:8px 0">{{ $disciplineCase->created_at?->format('d M Y H:i') }}</td></tr>
                        @if($disciplineCase->validated_at)<tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Divalidasi</td><td style="padding:8px 0">{{ $disciplineCase->validated_at?->format('d M Y H:i') }}</td></tr>@endif
                        @if($disciplineCase->closed_at)<tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Selesai</td><td style="padding:8px 0">{{ $disciplineCase->closed_at?->format('d M Y H:i') }}</td></tr>@endif
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
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Kelas</td><td style="padding:8px 0">{{ $disciplineCase->schoolClass?->name ?? $disciplineCase->student?->currentEnrollment?->schoolClass?->name ?? '-' }}</td></tr>
                    </table>
                </div>
                <div class="card" style="margin-bottom:var(--sp-lg)">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="tag" class="icon"></i> Pelanggaran</h2>
                    <table style="width:100%;border-collapse:collapse">
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted);width:140px">Kategori</td><td style="padding:8px 0;font-weight:600">{{ $disciplineCase->violationCategory?->parent?->name ?? '-' }}</td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Sub Kategori</td><td style="padding:8px 0;font-weight:600">{{ $disciplineCase->violationCategory?->name }} <span class="mono text-muted text-sm">{{ $disciplineCase->violationCategory?->code }}</span></td></tr>
                        @php
                            $sev = $disciplineCase->violationCategory?->severity;
                            $sevClass = in_array($sev, ['berat', 'sangat_berat']) ? 'badge-danger' : ($sev === 'sedang' ? 'badge-warning' : 'badge-info');
                            $sevLabel = $sev === 'sangat_berat' ? 'Sangat Berat (Zero Tolerance)' : ucfirst($sev ?? '-');
                        @endphp
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Tingkat</td><td style="padding:8px 0"><span class="badge {{ $sevClass }}">{{ $sevLabel }}</span></td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Tangga Toleransi</td><td style="padding:8px 0">Ke-{{ $disciplineCase->getCurrentTier() }} dari {{ count($disciplineCase->violationCategory?->tierPoints() ?? [1]) }} ({{ implode(' → ', $disciplineCase->violationCategory?->tierPoints() ?? []) }} poin)</td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Sanksi</td><td style="padding:8px 0">{{ $disciplineCase->violationCategory?->resolveSanksi() ?? '-' }}</td></tr>
                        <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Poin Dikurangi</td><td class="mono" style="padding:8px 0;font-size:1.25rem;font-weight:700;color:var(--danger)">-{{ $disciplineCase->getEffectivePoints() }}</td></tr>
                    </table>
                </div>
                <div class="card" style="margin-top:var(--sp-lg)">
                    <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="rotate-ccw" class="icon"></i> Rekomendasi Pemulihan Poin</h2>
                    @if($disciplineCase->violationCategory?->is_zero_tolerance)
                        <p class="text-sm" style="color:var(--danger);font-weight:600">Zero tolerance — Program Pembinaan Intensif, tidak bisa ditebus poin prestasi biasa.</p>
                    @elseif(count($recovery) > 0)
                        <div class="recovery-cards">
                            @foreach($recovery as $rec)
                                <div class="recovery-card">
                                    <div class="recovery-card-icon"><i data-lucide="trophy" style="width:18px;height:18px"></i></div>
                                    <div class="recovery-card-body">
                                        <div class="recovery-card-title">{{ $rec['name'] }}</div>
                                        <div class="text-sm text-muted">{{ $rec['code'] }} &middot; +{{ $rec['poin'] }} poin penebus</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-muted">Belum ada rekomendasi padanan untuk sub kategori ini.</p>
                    @endif
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
