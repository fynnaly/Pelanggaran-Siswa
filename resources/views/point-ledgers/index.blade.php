<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1><i data-lucide="wallet" class="icon"></i> Riwayat Poin</h1>
                <p class="text-sm text-muted" style="margin:0">Riwayat transaksi poin seluruh siswa</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <span class="text-sm text-muted" style="display:flex;align-items:center;gap:4px">
                    <i data-lucide="info" class="icon-sm"></i> Ledger append-only
                </span>
            </div>
        </div>
    </x-slot>

    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif

        {{-- Toolbar: Search + Filters --}}
        <div class="toolbar">
            <form method="GET" action="{{ route('point-ledgers.index') }}" style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;align-items:center;width:100%">
                <div style="position:relative;flex:1;min-width:200px;max-width:320px">
                    <i data-lucide="search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:var(--on-surface-muted);pointer-events:none"></i>
                    <input type="text" name="search" class="input" placeholder="Cari nama siswa atau NISN..." value="{{ $search }}" style="padding-left:36px">
                </div>
                <div class="filter-group">
                    <select name="direction" class="input" style="width:auto;min-width:140px" onchange="this.form.submit()">
                        <option value="">Semua Arah</option>
                        <option value="credit" {{ $direction==='credit'?'selected':'' }}>Kredit (+)</option>
                        <option value="debit" {{ $direction==='debit'?'selected':'' }}>Debit (-)</option>
                    </select>
                    <select name="type" class="input" style="width:auto;min-width:170px" onchange="this.form.submit()">
                        <option value="">Semua Tipe</option>
                        <option value="OPENING_BALANCE" {{ $type==='OPENING_BALANCE'?'selected':'' }}>Saldo Awal</option>
                        <option value="VIOLATION" {{ $type==='VIOLATION'?'selected':'' }}>Pelanggaran</option>
                        <option value="ACHIEVEMENT" {{ $type==='ACHIEVEMENT'?'selected':'' }}>Prestasi</option>
                        <option value="RECOVERY" {{ $type==='RECOVERY'?'selected':'' }}>Pemulihan</option>
                        <option value="REVERSAL" {{ $type==='REVERSAL'?'selected':'' }}>Reversal</option>
                    </select>
                    @if($search || $direction || $type)
                        <a href="{{ route('point-ledgers.index') }}" class="btn btn-secondary btn-sm">
                            <i data-lucide="x" class="icon-sm"></i> Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Siswa</th>
                        <th>Arah</th>
                        <th>Jumlah</th>
                        <th>Saldo</th>
                        <th>Tipe</th>
                        <th>Alasan</th>
                        <th style="width:70px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgers as $l)
                        <tr>
                            <td class="text-sm text-muted" style="white-space:nowrap">{{ $l->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:var(--sp-sm)">
                                    <div style="width:32px;height:32px;border-radius:var(--r-full);background:var(--primary);color:var(--on-primary);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0">
                                        {{ strtoupper(substr($l->student?->full_name ?? '?', 0, 2)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight:600;font-size:.875rem">{{ $l->student?->full_name ?? '-' }}</div>
                                        <div class="text-sm text-muted">{{ $l->student?->schoolClass?->name ?? '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($l->direction === 'credit')
                                    <span class="badge badge-success"><i data-lucide="trending-up" style="width:12px;height:12px"></i> Kredit</span>
                                @else
                                    <span class="badge badge-danger"><i data-lucide="trending-down" style="width:12px;height:12px"></i> Debit</span>
                                @endif
                            </td>
                            <td class="mono" style="font-weight:700;color:{{ $l->direction === 'credit' ? 'var(--tertiary)' : 'var(--danger)' }}">
                                {{ $l->direction === 'credit' ? '+' : '-' }}{{ number_format($l->amount) }}
                            </td>
                            <td class="mono" style="font-weight:700">{{ number_format($l->balance_after) }}</td>
                            <td>
                                @php
                                    $typeMap = [
                                        'OPENING_BALANCE' => ['label' => 'Saldo Awal', 'icon' => 'plus-circle', 'class' => 'badge-success'],
                                        'VIOLATION' => ['label' => 'Pelanggaran', 'icon' => 'alert-triangle', 'class' => 'badge-danger'],
                                        'ACHIEVEMENT' => ['label' => 'Prestasi', 'icon' => 'trophy', 'class' => 'badge-success'],
                                        'RECOVERY' => ['label' => 'Pemulihan', 'icon' => 'rotate-ccw', 'class' => ''],
                                        'REVERSAL' => ['label' => 'Reversal', 'icon' => 'undo-2', 'class' => 'badge-warning'],
                                    ];
                                    $t = $typeMap[$l->transaction_type] ?? ['label' => $l->transaction_type, 'icon' => 'file-text', 'class' => ''];
                                @endphp
                                <span class="badge {{ $t['class'] }}" style="{{ $t['class'] === '' ? 'background:var(--surface);color:var(--on-surface-muted)' : '' }}">
                                    <i data-lucide="{{ $t['icon'] }}" style="width:12px;height:12px"></i> {{ $t['label'] }}
                                </span>
                            </td>
                            <td class="text-sm text-muted">{{ Str::limit($l->reason ?? '-', 40) }}</td>
                            <td>
                                <a href="{{ route('point-ledgers.show', $l) }}" class="action-btn" title="Detail">
                                    <i data-lucide="eye" style="width:14px;height:14px"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="padding:40px;text-align:center;color:var(--on-surface-muted)">
                            <i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>
                            Belum ada transaksi poin.
                            @if($search || $direction || $type)
                                <br><a href="{{ route('point-ledgers.index') }}" style="font-size:.8125rem;margin-top:8px;display:inline-block">Hapus filter</a>
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination + count --}}
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-sm);margin-top:var(--sp-lg)">
            <span class="text-sm text-muted">
                Menampilkan {{ $ledgers->firstItem() ?? 0 }}-{{ $ledgers->lastItem() ?? 0 }} dari {{ $ledgers->total() }}
            </span>
            <div class="pagination" style="margin-top:0">{{ $ledgers->links() }}</div>
        </div>
    </div>

    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
