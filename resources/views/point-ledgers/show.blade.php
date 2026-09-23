<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1><i data-lucide="book-open" class="icon"></i> Riwayat Poin Siswa</h1>
                <p class="text-sm text-muted" style="margin:0">{{ $student?->full_name ?? '-' }} ({{ $student?->schoolClass?->name ?? '-' }})</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        {{-- Student Info Card --}}
        <div class="card" style="margin-bottom:var(--sp-xl)">
            <div style="display:flex;align-items:center;gap:var(--sp-lg);flex-wrap:wrap">
                <div style="width:56px;height:56px;border-radius:var(--r-full);background:var(--primary);color:var(--on-primary);display:flex;align-items:center;justify-content:center;font-size:1.25rem;font-weight:700;flex-shrink:0">
                    {{ strtoupper(substr($student?->full_name ?? '?', 0, 2)) }}
                </div>
                <div style="flex:1;min-width:200px">
                    <div style="font-weight:700;font-size:1.125rem">{{ $student?->full_name ?? '-' }}</div>
                    <div class="text-sm text-muted">NISN: {{ $student?->nisn ?? '-' }} &middot; Kelas: {{ $student?->schoolClass?->name ?? '-' }}</div>
                </div>
                <div style="text-align:right">
                    <div class="stat-label">Saldo Saat Ini</div>
                    <div style="font-size:1.5rem;font-weight:700;font-family:ui-monospace,monospace;color:{{ $ledger->isNotEmpty() && $ledger->first()->balance_after <= 500 ? 'var(--danger)' : 'var(--on-surface)' }}">
                        @if($ledger->isNotEmpty()){{ number_format($ledger->first()->balance_after) }}@else-@endif
                    </div>
                    @if($ledger->isNotEmpty() && $ledger->first()->balance_after <= 500)
                        <span class="badge badge-danger" style="margin-top:4px"><i data-lucide="alert-triangle" style="width:12px;height:12px"></i> Saldo rendah</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Transaction Table --}}
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Tanggal</th>
                        <th>Kasus Terkait</th>
                        <th>Keterangan</th>
                        <th>Tipe</th>
                        <th>Poin</th>
                        <th>Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledger as $i => $entry)
                        <tr>
                            <td class="mono text-muted">{{ $i + 1 }}</td>
                            <td class="text-sm text-muted" style="white-space:nowrap">{{ $entry->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($entry->discipline_case_id)
                                    <a href="{{ route('kasus-pelanggaran.show', $entry->discipline_case_id) }}" style="font-weight:600;display:inline-flex;align-items:center;gap:4px">
                                        <i data-lucide="file-text" style="width:14px;height:14px"></i> {{ $entry->case?->case_number ?? '#' . $entry->discipline_case_id }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-sm">{{ Str::limit($entry->reason ?? '-', 40) }}</td>
                            <td>
                                @php
                                    $typeMap = [
                                        'OPENING_BALANCE' => ['label' => 'Saldo Awal', 'icon' => 'plus-circle', 'class' => 'badge-success'],
                                        'VIOLATION' => ['label' => 'Pelanggaran', 'icon' => 'alert-triangle', 'class' => 'badge-danger'],
                                        'ACHIEVEMENT' => ['label' => 'Prestasi', 'icon' => 'trophy', 'class' => 'badge-success'],
                                        'RECOVERY' => ['label' => 'Pemulihan', 'icon' => 'rotate-ccw', 'class' => ''],
                                        'REVERSAL' => ['label' => 'Reversal', 'icon' => 'undo-2', 'class' => 'badge-warning'],
                                    ];
                                    $t = $typeMap[$entry->transaction_type] ?? ['label' => $entry->transaction_type, 'icon' => 'file-text', 'class' => ''];
                                @endphp
                                <span class="badge {{ $t['class'] }}" style="{{ $t['class'] === '' ? 'background:var(--surface);color:var(--on-surface-muted)' : '' }}">
                                    <i data-lucide="{{ $t['icon'] }}" style="width:12px;height:12px"></i> {{ $t['label'] }}
                                </span>
                            </td>
                            <td class="mono" style="font-weight:700;color:{{ $entry->direction === 'credit' ? 'var(--tertiary)' : 'var(--danger)' }}">
                                {{ $entry->direction === 'credit' ? '+' : '-' }}{{ number_format($entry->amount) }}
                            </td>
                            <td class="mono" style="font-weight:700">{{ number_format($entry->balance_after) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="padding:40px;text-align:center;color:var(--on-surface-muted)">
                            <i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada transaksi poin.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
