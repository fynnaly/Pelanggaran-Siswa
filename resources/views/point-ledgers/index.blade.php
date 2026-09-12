<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buku Poin (Ledger)</h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Waktu</th>
                                <th class="px-4 py-3 text-left font-semibold">Siswa</th>
                                <th class="px-4 py-3 text-center font-semibold">Arah</th>
                                <th class="px-4 py-3 text-center font-semibold">Jumlah</th>
                                <th class="px-4 py-3 text-center font-semibold">Saldo Akhir</th>
                                <th class="px-4 py-3 text-left font-semibold">Tipe</th>
                                <th class="px-4 py-3 text-left font-semibold">Alasan</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($ledgers as $l)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ $l->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3">{{ $l->student?->full_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center"><span class="px-2 py-1 rounded-full text-xs font-semibold {{ $l->direction==='debit'?'bg-red-100 text-red-700':'bg-emerald-100 text-emerald-700' }}">{{ $l->direction }}</span></td>
                                    <td class="px-4 py-3 text-center font-semibold">{{ $l->amount }}</td>
                                    <td class="px-4 py-3 text-center font-bold">{{ $l->balance_after }}</td>
                                    <td class="px-4 py-3"><span class="px-2 py-1 rounded bg-gray-100 text-xs">{{ $l->transaction_type }}</span></td>
                                    <td class="px-4 py-3 text-xs text-gray-600">{{ $l->reason ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right"><a href="{{ route('point-ledgers.show', $l) }}" class="text-blue-600 hover:underline text-xs">Detail</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-10 text-center text-gray-500">Belum ada transaksi poin.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">{{ $ledgers->links() }}</div>
            </div>
            <p class="mt-3 text-xs text-gray-500">Ledger bersifat append-only. Koreksi saldo pakai entri baru tipe REVERSAL, bukan edit/hapus.</p>
        </div>
    </div>
</x-app-layout>
