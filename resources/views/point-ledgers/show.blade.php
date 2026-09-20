<x-app-layout>
    <x-slot name="header">

        <div class="page-header">
            <div><h1 style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="book-open" class="icon"></i> Ledger Poin Siswa</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card" style="margin-bottom:var(--sp-lg)">
            <table style="width:100%;border-collapse:collapse">
                <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted);width:140px">Siswa</td><td style="padding:8px 0;font-weight:600">{{ $student->full_name }}</td></tr>
                <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Kelas</td><td style="padding:8px 0">{{ $student->schoolClass?->name ?? '-' }}</td></tr>
                <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Saldo Saat Ini</td><td style="padding:8px 0;font-weight:700;font-size:1.25rem;{{ $ledger->isNotEmpty() && $ledger->first()->balance_after <= 500 ? 'color:var(--danger)' : '' }}">{{ $ledger->isNotEmpty() ? $ledger->first()->balance_after : '-' }}</td></tr>
                <tr><td class="label" style="padding:8px 0;color:var(--on-surface-muted)">Total Transaksi</td><td style="padding:8px 0;font-weight:600">{{ $ledger->count() }}</td></tr>
            </table>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th style="width:40px">#</th><th>Tanggal</th><th>Kasus Terkait</th><th>Keterangan</th><th>Poin</th><th>Saldo</th></tr></thead>
                <tbody>
                    @forelse($ledger as $i => $entry)
                        <tr>
                            <td class="mono">{{ $i + 1 }}</td>
                            <td class="text-sm text-muted">{{ $entry->created_at?->format('d/m/Y H:i') }}</td>
                            <td><a href="{{ route('kasus-pelanggaran.show', $entry->discipline_case_id) }}" style="color:var(--tertiary);font-weight:600;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">{{ $entry->case?->case_number ?? '-' }}</a></td>
                            <td class="text-sm">{{ $entry->description ?? '-' }}</td>
                            <td class="mono" style="font-weight:700;color:var(--danger)">{{ $entry->points > 0 ? '+' : '' }}{{ $entry->points }}</td>
                            <td class="mono" style="font-weight:700">{{ $entry->balance_after }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada transaksi poin.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($ledger->hasPages())<div style="display:flex;justify-content:flex-end;margin-top:var(--sp-lg)"><div class="pagination" style="margin-top:0">{{ $ledger->links() }}</div></div>@endif
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>