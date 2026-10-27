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
                        <option value="handling" {{ $status==='handling'?'selected':'' }}>Penanganan</option>
                        <option value="closed" {{ $status==='closed'?'selected':'' }}>Selesai</option>
                        <option value="dismissed" {{ $status==='dismissed'?'selected':'' }}>Dibuang</option>
                    </select>
                </div>
            </form>
        </div>

        @php $canBulk = auth()->user()?->hasAnyRole(['bk','kesiswaan','admin']); @endphp
        @if($canBulk)
        <form id="bulkForm" method="POST" action="{{ route('kasus-pelanggaran.bulk-status') }}" style="display:none;align-items:center;gap:var(--sp-sm);flex-wrap:wrap;margin-bottom:var(--sp-md);padding:var(--sp-sm) var(--sp-md);background:var(--surface);border:1px solid var(--border);border-radius:var(--r-sm)">
            @csrf
            <span class="text-sm" style="font-weight:600"><span id="bulkCount">0</span> dipilih</span>
            <span class="text-sm text-muted" style="margin-right:var(--sp-xs)">Majukan ke:</span>
            <select name="status" class="input" style="width:auto;min-width:160px" required>
                <option value="" disabled selected>Pilih tahap</option>
                <option value="handling">Penanganan — potong poin</option>
                <option value="closed">Selesai</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm" id="bulkSubmit" disabled><i data-lucide="arrow-right" class="icon-sm"></i> Majukan</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="clearBulk()">Batal</button>
            <span class="text-sm text-muted" style="margin-left:auto">Hanya kasus yang masih di belakang tahap tujuan yang dimajukan — sisanya dilewati. Poin hanya dipotong sekali saat masuk Penanganan.</span>
        </form>
        @endif

        <div id="caseRegion">
        <div class="table-wrap">
            <table>
                <thead><tr>
                    @if($canBulk)<th style="width:36px;text-align:center"><input type="checkbox" id="selectAll" title="Pilih semua di halaman ini"></th>@endif
                    <th style="width:40px"><x-sortable-header column="id" label="No" :sort="$sort" :order="$order" route="kasus-pelanggaran.index" :params="request()->except('sort','order','page')" /></th>
                    <th><x-sortable-header column="case_number" label="No. Kasus" :sort="$sort" :order="$order" route="kasus-pelanggaran.index" :params="request()->except('sort','order','page')" /></th>
                    <th><x-sortable-header column="student_id" label="Siswa" :sort="$sort" :order="$order" route="kasus-pelanggaran.index" :params="request()->except('sort','order','page')" /></th>
                    <th><x-sortable-header column="violation_category_id" label="Pelanggaran" :sort="$sort" :order="$order" route="kasus-pelanggaran.index" :params="request()->except('sort','order','page')" /></th>
                    <th style="width:70px">Poin</th>
                    <th><x-sortable-header column="status" label="Status" :sort="$sort" :order="$order" route="kasus-pelanggaran.index" :params="request()->except('sort','order','page')" /></th>
                    <th><x-sortable-header column="incident_date" label="Tanggal" :sort="$sort" :order="$order" route="kasus-pelanggaran.index" :params="request()->except('sort','order','page')" /></th>
                    <th style="width:100px">Aksi</th>
                </tr></thead>
                <tbody>
                    @php
                        // ponytail: alur ringkas 1-klik (found -> handling -> closed). Kasus lama tetap dipetakan maju.
                        $nextStatus = [
                            'found' => 'handling',
                            'clarifying' => 'handling',
                            'classifying' => 'handling',
                            'handling' => 'closed',
                            'monitoring' => 'closed',
                        ];
                        $nextLabels = [
                            'handling' => 'Penanganan',
                            'closed' => 'Selesai',
                        ];
                    @endphp
                    @forelse($cases as $idx => $case)
                        @php
                            $cat = $case->violationCategory;
                            $points = $case->getEffectivePoints();
                            $code = $cat ? ($cat->code ?? '-') : '-';
                            $catName = $cat ? ($cat->name ?? '-') : '-';
                            $incDate = $case->incident_date ?? $case->created_at;
                        @endphp
                        <tr>
                            @if($canBulk)<td style="text-align:center"><input type="checkbox" class="case-check" name="ids[]" value="{{ $case->id }}" form="bulkForm"></td>@endif
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
                                @can('update', $case)
                                    @php $next = $nextStatus[$case->status] ?? null; @endphp
                                    @if($next)
                                        <form action="{{ route('kasus-pelanggaran.update-status', $case) }}" method="POST" class="inline" onsubmit="return confirm('Majukan ke {{ $nextLabels[$next] }}{{ $next === 'handling' ? ' — poin akan dipotong' : '' }}?')">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $next }}"><button class="action-btn" title="Lanjut ke {{ $nextLabels[$next] }}" style="color:var(--primary)"><i data-lucide="arrow-right-circle" style="width:14px;height:14px"></i></button></form>
                                    @endif
                                @endcan
                                <a href="{{ route('kasus-pelanggaran.show', $case) }}" class="action-btn" title="Lihat"><i data-lucide="eye" style="width:14px;height:14px"></i></a>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canBulk ? 9 : 8 }}" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada kasus.</td></tr>
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
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
        const bulkForm = document.getElementById('bulkForm');
        if (!bulkForm) return;
        const selectAll = document.getElementById('selectAll');
        const countEl = document.getElementById('bulkCount');
        const submitBtn = document.getElementById('bulkSubmit');
        const statusSel = bulkForm.querySelector('select[name=\"status\"]');
        const caseRegion = document.getElementById('caseRegion');

        function getChecks() { return document.querySelectorAll('.case-check'); }

        function refresh() {
            const checks = getChecks();
            const checked = document.querySelectorAll('.case-check:checked');
            const n = checked.length;
            countEl.textContent = n;
            bulkForm.style.display = n ? 'flex' : 'none';
            const hasStatus = statusSel.value !== '';
            submitBtn.disabled = !(n && hasStatus);
            if (selectAll) {
                selectAll.checked = checks.length > 0 && n === checks.length;
                selectAll.indeterminate = n > 0 && n < checks.length;
            }
            // simpan ids terpilih agar tidak hilang saat live filter replace #caseRegion
            // checkbox di dalam region memakai form="bulkForm" (HTML5) — tetap terkirim walau di luar form
        }

        window.clearBulk = () => {
            getChecks().forEach(c => c.checked = false);
            statusSel.value = '';
            refresh();
        };

        if (selectAll) selectAll.addEventListener('change', () => {
            getChecks().forEach(c => c.checked = selectAll.checked);
            refresh();
        });

        statusSel.addEventListener('change', refresh);

        // delegation — tetap bekerja setelah #caseRegion di-replace oleh live filter
        caseRegion.addEventListener('change', e => {
            if (e.target.classList.contains('case-check')) refresh();
        });

        // setelah live filter selesai, icons + refresh state
        const obs = new MutationObserver(() => { lucide.createIcons(); refresh(); });
        obs.observe(caseRegion, { childList: true, subtree: true });

        bulkForm.addEventListener('submit', e => {
            const n = document.querySelectorAll('.case-check:checked').length;
            if (!n) { e.preventDefault(); return; }
            const label = statusSel.options[statusSel.selectedIndex]?.text || statusSel.value;
            if (!confirm('Majukan ' + n + ' kasus ke \"' + label + '\"?')) e.preventDefault();
        });

        refresh();
    });
    </script>
</x-app-layout>
