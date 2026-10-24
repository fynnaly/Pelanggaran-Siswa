<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Monitor Poin</h1>
                <p class="text-muted text-sm" data-live-count>{{ $paginated->total() }} siswa &middot; tahun ajaran aktif</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap" class="no-print">
                <a href="{{ route('monitor-poin.export') }}?{{ http_build_query(request()->only(['q','year_id','class_id','zone'])) }}" class="btn btn-secondary btn-sm"><i data-lucide="download" class="icon-sm"></i> Export CSV</a>
                <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm"><i data-lucide="printer" class="icon-sm"></i> Cetak Rekap</button>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif

        <form id="bulkCertForm" action="{{ route('monitor-poin.bulk-certificates') }}" method="POST" class="no-print">
            @csrf
            <div style="display:flex;gap:var(--sp-sm);align-items:center;margin-bottom:var(--sp-md);flex-wrap:wrap">
                <label style="display:inline-flex;align-items:center;gap:6px;font-size:.8125rem;color:var(--on-surface-muted);cursor:pointer">
                    <input type="checkbox" id="checkAllCert" style="width:15px;height:15px;accent-color:var(--primary)"> Pilih semua
                </label>
                <button type="submit" class="btn btn-primary btn-sm" id="bulkCertBtn" disabled><i data-lucide="download" class="icon-sm"></i> Unduh ZIP (<span id="certCount">0</span>)</button>
                <a href="#" class="btn btn-secondary btn-sm" id="bulkPrintBtn" style="pointer-events:none;opacity:.5"><i data-lucide="printer" class="icon-sm"></i> Cetak Semua (<span id="printCount">0</span>)</a>
                <span class="text-sm text-muted">ZIP = 1 file/siswa (Sertifikat-Poin_Nama_Kelas_Tahun.html). Cetak = 1 halaman semua sertifikat (page-break/siswa).</span>
            </div>
        </form>

        <div class="toolbar no-print">
            <form id="poinFilter" action="{{ route('monitor-poin.index') }}" method="GET" data-live="#poinRegion" style="display:flex;gap:var(--sp-sm);flex:1;align-items:stretch;flex-wrap:wrap">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / NISN / NIS..." class="input" style="flex:1;min-width:180px" id="poinSearch">
                <select name="year_id" class="input no-print" style="width:auto;min-width:110px;flex-shrink:0">
                    <option value="">Semua Tahun</option>
                    @foreach($academicYears as $ay)
                        <option value="{{ $ay->id }}" {{ (string)($selectedYearId ?? '')===(string)$ay->id?'selected':'' }}>{{ $ay->name }}</option>
                    @endforeach
                </select>
                <select name="class_id" class="input no-print" style="width:auto;min-width:140px;flex-shrink:0">
                    <option value="">Semua Kelas</option>
                    @foreach($allClasses as $cls)
                        <option value="{{ $cls->id }}" {{ (string)request('class_id')===(string)$cls->id?'selected':'' }}>{{ $cls->name }}</option>
                    @endforeach
                </select>
                <select name="zone" class="input no-print" style="width:auto;min-width:160px;flex-shrink:0">
                    <option value="">Semua Zona</option>
                    @foreach(config('zones.zones',[]) as $z)
                        <option value="{{ $z['name'] }}" {{ request('zone')===$z['name']?'selected':'' }}>{{ $z['name'] }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div id="poinRegion">
            @php
                $baseParams = array_filter(['q'=>request('q'),'year_id'=>request('year_id'),'class_id'=>request('class_id'),'zone'=>request('zone')]);
                $sortArrow = function($col) use ($sort,$order){
                    $active = $sort===$col;
                    if(!$active) return '<i data-lucide="arrow-up-down" style="width:12px;height:12px;color:var(--on-surface-muted);margin-left:2px;vertical-align:middle"></i>';
                    $icon = $order==='asc' ? 'arrow-up' : 'arrow-down';
                    return '<i data-lucide="'.$icon.'" style="width:12px;height:12px;color:var(--primary);margin-left:2px;vertical-align:middle"></i>';
                };
                $sortLink = function($col) use ($baseParams,$sort,$order){
                    if($sort===$col && $order==='asc') return array_merge($baseParams,['sort'=>$col,'order'=>'desc']);
                    if($sort===$col && $order==='desc') return $baseParams;
                    return array_merge($baseParams,['sort'=>$col,'order'=>'asc']);
                };
                $zoneBadge = function($zone){
                    $c = $zone['color'] ?? 'success';
                    $map = ['danger'=>'badge-danger','warning'=>'badge-warning','info'=>'badge-info','success'=>'badge-success'];
                    return $map[$c] ?? 'badge-success';
                };
                $isTop2 = fn($name) => in_array($name, ['Istimewa','Luar Biasa'], true);
            @endphp
            <div class="table-wrap">
                <table>
                    <thead><tr><th style="width:34px" class="no-print"><input type="checkbox" id="checkAllCert2" title="Pilih semua" style="width:15px;height:15px;accent-color:var(--primary)"></th><th style="width:48px"><x-sortable-header column="id" label="No" :sort="$sort" :order="$order" route="monitor-poin.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="nama" label="Nama" :sort="$sort" :order="$order" route="monitor-poin.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="nis" label="NIS" :sort="$sort" :order="$order" route="monitor-poin.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="kelas" label="Kelas" :sort="$sort" :order="$order" route="monitor-poin.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="poin" label="Poin" :sort="$sort" :order="$order" route="monitor-poin.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="zona" label="Zona" :sort="$sort" :order="$order" route="monitor-poin.index" :params="request()->except('sort','order','page')" /></th><th style="width:100px">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($paginated as $student)
                            @php
                                $enr = $student->enrollments->sortByDesc('id')->first();
                                $balance = $student->current_balance;
                                $zone = $student->zone;
                                $top = $isTop2($zone['name']);
                                $badge = $zoneBadge($zone);
                                $isIstimewa = $zone['name']==='Istimewa';
                            @endphp
                            <tr>
                                <td class="no-print"><input type="checkbox" form="bulkCertForm" class="cert-check" name="ids[]" value="{{ $student->id }}" style="width:15px;height:15px;accent-color:var(--primary)"></td>
                                <td class="text-sm text-muted">{{ $student->id }}</td>
                                <td><strong>{{ $student->full_name }}</strong><div class="text-sm text-muted mono" style="font-size:.75rem">{{ $student->nisn }}</div></td>
                                <td class="mono">{{ $student->nis }}</td>
                                <td class="text-sm" style="white-space:nowrap">{{ $enr?->schoolClass?->name ?? '-' }}<div class="text-sm text-muted" style="font-size:.75rem">{{ $enr?->schoolClass?->grade_level ?? '' }}</div></td>
                                <td class="mono" style="font-weight:700;white-space:nowrap">{{ number_format($balance) }}</td>
                                <td>
                                    @if($isIstimewa)
                                        <span class="badge" style="background:var(--primary);color:#fff">{{ $zone['name'] }}</span>
                                    @else
                                        <span class="badge {{ $badge }}">{{ $zone['name'] }}</span>
                                    @endif
                                </td>
                                <td class="no-print"><div class="action-cell">
                                    <a href="{{ route('point-ledgers.siswa', $student) }}" class="action-btn" title="Detail"><i data-lucide="eye" style="width:14px;height:14px"></i></a>
                                    <a href="{{ route('monitor-poin.certificate', $student) }}" target="_blank" class="action-btn" title="Sertifikat {{ $zone['name'] }}" style="color:var(--tertiary);border-color:var(--tertiary)"><i data-lucide="award" style="width:14px;height:14px"></i></a>
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada siswa di tahun ajaran aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex-wrap:wrap;margin-top:var(--sp-lg)">
                <span class="text-sm text-muted">Menampilkan {{ $paginated->firstItem() ?? 0 }}-{{ $paginated->lastItem() ?? 0 }} dari {{ $paginated->total() }}</span>
                <div class="pagination" style="margin-top:0">{{ $paginated->links() }}</div>
            </div>
        </div>
    </div>
    <style>
        @media print {
            .no-print, .app-header, aside, .sidebar, .sidebar-mobile, .sidebar-overlay, .mobile-topbar,
            .pagination, .action-cell { display:none !important; }
            .app-shell { margin-left:0 !important; }
            .main-wrap { padding:0 !important; max-width:none !important; }
            .table-wrap { border:1px solid #000 !important; border-radius:0 !important; }
            a[href]:after { content:none !important; }
            @page { size:A4 landscape; margin:12mm; }
        }
    </style>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
    <script>
        // Bulk sertifikat: pilih baris → submit ZIP, atau buka halaman cetak gabungan (PDF multi-halaman).
        document.addEventListener('DOMContentLoaded',()=>{
            const form=document.getElementById('bulkCertForm');
            const countEl=document.getElementById('certCount');
            const printCountEl=document.getElementById('printCount');
            const btn=document.getElementById('bulkCertBtn');
            const printBtn=document.getElementById('bulkPrintBtn');
            const all=document.getElementById('checkAllCert');
            const all2=document.getElementById('checkAllCert2');
            function ids(){return [...document.querySelectorAll('.cert-check:checked')].map(c=>c.value)}
            function refresh(){
                const n=ids().length;
                countEl.textContent=n; printCountEl.textContent=n;
                btn.disabled=n===0;
                printBtn.style.pointerEvents=n===0?'none':'auto';
                printBtn.style.opacity=n===0?'.5':'1';
            }
            form.addEventListener('change',refresh);
            document.getElementById('poinRegion').addEventListener('change',refresh);
            function toggleAll(v){document.querySelectorAll('.cert-check').forEach(c=>{c.checked=v});all.checked=v;all2.checked=v;refresh()}
            all.addEventListener('change',e=>toggleAll(e.target.checked));
            all2.addEventListener('change',e=>toggleAll(e.target.checked));
            form.addEventListener('submit',e=>{
                if(btn.disabled){e.preventDefault();return}
                btn.disabled=true;btn.textContent='Membuat arsip...';
            });
            printBtn.addEventListener('click',e=>{
                e.preventDefault();
                const q=ids();
                if(!q.length)return;
                const url=new URL("{{ route('monitor-poin.print-certificates') }}",location.origin);
                q.forEach(id=>url.searchParams.append('ids[]',id));
                window.open(url.toString(),'_blank');
            });
            refresh();
        });
    </script>
</x-app-layout>
