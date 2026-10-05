<x-app-layout>
    <x-slot name="header">

        <div class="page-header">
            <div>
                <h1>Kasus Pelanggaran</h1>
                <p class="text-muted text-sm" data-live-count>{{ $cases->total() }} total kasus</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('kasus-pelanggaran.create') }}" class="btn btn-primary"><i data-lucide="plus" class="icon-sm"></i> Buat Kasus</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if(session('warning'))<div class="alert alert-warning"><i data-lucide="alert-triangle" class="icon-sm"></i> {{ session('warning') }}</div>@endif
        @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
        <div class="toolbar">
            <form method="GET" action="{{ route('kasus-pelanggaran.index') }}" data-live="#caseRegion" id="caseFilter" style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;align-items:center;width:100%">
                <input type="text" name="search" class="input" placeholder="Cari nomor kasus, nama siswa..." style="max-width:280px" value="{{ request('search') }}">
                <div class="filter-group">
                    <select name="status" class="input" style="width:auto;min-width:150px" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="found" {{ $status==='found'?'selected':'' }}>Temuan</option>
                        <option value="clarifying" {{ $status==='clarifying'?'selected':'' }}>Klarifikasi</option>
                        <option value="classifying" {{ $status==='classifying'?'selected':'' }}>Klasifikasi</option>
                        <option value="handling" {{ $status==='handling'?'selected':'' }}>Penanganan</option>
                        <option value="monitoring" {{ $status==='monitoring'?'selected':'' }}>Monitoring</option>
                        <option value="closed" {{ $status==='closed'?'selected':'' }}>Selesai</option>
                        <option value="dismissed" {{ $status==='dismissed'?'selected':'' }}>Dibuang</option>
                    </select>
                </div>
            </form>
        </div>
        <div id="caseRegion">
        <div class="table-wrap">
            <table>
                <thead><tr><th style="width:40px"><x-sortable-header column="id" label="No" :sort="$sort" :order="$order" route="kasus-pelanggaran.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="case_number" label="No. Kasus" :sort="$sort" :order="$order" route="kasus-pelanggaran.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="student_id" label="Siswa" :sort="$sort" :order="$order" route="kasus-pelanggaran.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="violation_category_id" label="Pelanggaran" :sort="$sort" :order="$order" route="kasus-pelanggaran.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="status" label="Status" :sort="$sort" :order="$order" route="kasus-pelanggaran.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="incident_date" label="Tanggal" :sort="$sort" :order="$order" route="kasus-pelanggaran.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="created_at" label="Dibuat" :sort="$sort" :order="$order" route="kasus-pelanggaran.index" :params="request()->except('sort','order','page')" /></th><th style="width:100px">Aksi</th></tr></thead>
                <tbody>
                    @forelse($cases as $idx => $case)
                        @php
                            $cat = $case->violationCategory;
                            $points = $case->getEffectivePoints();
                            $code = $cat ? ($cat->code ?? '-') : '-';
                            $catName = $cat ? ($cat->name ?? '-') : '-';
                            $incDate = $case->incident_date ?? $case->created_at;
                        @endphp
                        <tr>
                            <td class="mono" style="text-align:center;color:var(--on-surface-muted);font-size:.75rem">{{ $case->id }}</td>
                            <td class="mono">{{ $case->case_number }}</td>
                            <td><strong>{{ $case->student?->full_name ?? '-' }}</strong><br><span class="text-muted text-sm">{{ $case->schoolClass?->name ?? $case->student?->currentEnrollment?->schoolClass?->name ?? '' }}</span></td>
                            <td>
                                @if($cat)<span class="mono" style="font-size:.75rem;color:var(--on-surface-muted)">{{ $code }}</span> {{ $catName }}
                                @else<span class="text-muted text-sm">-</span>@endif
                            </td>
                            <td class="mono" style="color:var(--danger);font-weight:600">-{{ $points }}</td>
                            <td>
                                @if($case->status==='found')<span class="badge badge-warning"><i data-lucide="clock" style="width:12px;height:12px"></i> Temuan</span>
                                @elseif($case->status==='clarifying')<span class="badge badge-warning"><i data-lucide="message-circle" style="width:12px;height:12px"></i> Klarifikasi</span>
                                @elseif($case->status==='classifying')<span class="badge" style="background:var(--ws);color:var(--wt)"><i data-lucide="tag" style="width:12px;height:12px"></i> Klasifikasi</span>
                                @elseif($case->status==='handling')<span class="badge" style="background:var(--ws);color:var(--wt)"><i data-lucide="gavel" style="width:12px;height:12px"></i> Penanganan</span>
                                @elseif($case->status==='monitoring')<span class="badge badge-warning"><i data-lucide="eye" style="width:12px;height:12px"></i> Monitoring</span>
                                @elseif($case->status==='closed')<span class="badge badge-success"><i data-lucide="check-circle" style="width:12px;height:12px"></i> Selesai</span>
                                @else<span class="badge badge-danger"><i data-lucide="x-circle" style="width:12px;height:12px"></i> Dibuang</span>@endif
                            </td>
                            <td class="text-sm text-muted">{{ $incDate?->format('d M Y') }}</td>
                            <td><div class="action-cell">
                                <a href="{{ route('kasus-pelanggaran.show', $case) }}" class="action-btn" title="Lihat"><i data-lucide="eye" style="width:14px;height:14px"></i></a>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada kasus.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-sm);margin-top:var(--sp-lg)">
            <span class="text-sm text-muted">Menampilkan {{ $cases->firstItem() ?? 0 }}-{{ $cases->lastItem() ?? 0 }} dari {{ $cases->total() }} kasus</span>
            <div class="pagination" style="margin-top:0">{{ $cases->links() }}</div>
        </div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
