<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Ledger #{{ $pointLedger->id }}</h2>
            <a href="{{ route('point-ledgers.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Kembali</a>
        </div>
    </x-slot>
    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4 text-sm">
                <div class="grid grid-cols-2 gap-4">
                    <div><div class="text-gray-500">Siswa</div><div class="font-semibold">{{ $pointLedger->student?->full_name ?? '-' }}</div></div>
                    <div><div class="text-gray-500">Tahun Ajaran</div><div class="font-semibold">{{ $pointLedger->academicYear?->name ?? $pointLedger->academic_year_id }}</div></div>
                    <div><div class="text-gray-500">Arah</div><div><span class="px-2 py-1 rounded-full text-xs font-semibold {{ $pointLedger->direction==='debit'?'bg-red-100 text-red-700':'bg-emerald-100 text-emerald-700' }}">{{ $pointLedger->direction }}</span></div></div>
                    <div><div class="text-gray-500">Jumlah</div><div class="font-bold text-lg">{{ $pointLedger->amount }}</div></div>
                    <div><div class="text-gray-500">Saldo Akhir</div><div class="font-bold text-lg">{{ $pointLedger->balance_after }}</div></div>
                    <div><div class="text-gray-500">Tipe</div><div><span class="px-2 py-1 rounded bg-gray-100 text-xs">{{ $pointLedger->transaction_type }}</span></div></div>
                    <div><div class="text-gray-500">Sumber</div><div class="font-mono text-xs">{{ $pointLedger->source_type ?? '-' }} #{{ $pointLedger->source_id ?? '-' }}</div></div>
                    <div><div class="text-gray-500">Waktu</div><div>{{ $pointLedger->created_at?->format('d/m/Y H:i:s') }}</div></div>
                </div>
                <div><div class="text-gray-500">Alasan</div><div class="mt-1 p-3 bg-gray-50 rounded-lg">{{ $pointLedger->reason ?? '-' }}</div></div>
            </div>
        </div>
    </div>
</x-app-layout>
