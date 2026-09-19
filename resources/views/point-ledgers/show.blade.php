<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Buku Poin / Detail</div>
        <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-sm)">
            <div><h1>Detail Ledger #{{ $pointLedger->id }}</h1></div>
            <a href="{{ route('point-ledgers.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card" style="max-width:700px">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--sp-lg)">
                <div>
                    <div class="label">Siswa</div>
                    <div style="margin-top:var(--sp-xs);font-weight:600">{{ $pointLedger->student?->full_name ?? '-' }}</div>
                </div>
                <div>
                    <div class="label">Tahun Ajaran</div>
                    <div style="margin-top:var(--sp-xs);font-weight:600">{{ $pointLedger->academicYear?->name ?? $pointLedger->academic_year_id }}</div>
                </div>
                <div>
                    <div class="label">Arah</div>
                    <div style="margin-top:var(--sp-xs)"><span class="badge {{ $pointLedger->direction==='debit' ? 'badge-danger' : 'badge-success' }}">{{ $pointLedger->direction }}</span></div>
                </div>
                <div>
                    <div class="label">Jumlah</div>
                    <div style="margin-top:var(--sp-xs);font-weight:700;font-size:1.125rem">{{ $pointLedger->amount }}</div>
                </div>
                <div>
                    <div class="label">Saldo Akhir</div>
                    <div style="margin-top:var(--sp-xs);font-weight:700;font-size:1.125rem">{{ $pointLedger->balance_after }}</div>
                </div>
                <div>
                    <div class="label">Tipe</div>
                    <div style="margin-top:var(--sp-xs)"><span class="badge" style="background:var(--surface);color:var(--on-surface)">{{ $pointLedger->transaction_type }}</span></div>
                </div>
                <div>
                    <div class="label">Sumber</div>
                    <div style="margin-top:var(--sp-xs);font-family:ui-monospace;font-size:.8125rem">{{ $pointLedger->sourceLabel() }}</div>
                </div>
                <div>
                    <div class="label">Waktu</div>
                    <div style="margin-top:var(--sp-xs)">{{ $pointLedger->created_at?->format('d/m/Y H:i:s') }}</div>
                </div>
            </div>
            <div style="margin-top:var(--sp-lg);border-top:1px solid var(--border);padding-top:var(--sp-lg)">
                <div class="label">Alasan</div>
                <div style="margin-top:var(--sp-xs);padding:var(--sp-md);background:var(--surface);border-radius:var(--r-sm)">{{ $pointLedger->reason ?? '-' }}</div>
            </div>
        </div>
    </div>
</x-app-layout>