<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kasus Pelanggaran</h2>
            <a href="{{ route('discipline-cases.create') }}" class="px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700">+ Lapor Kasus</a>
        </div>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
                <div class="mb-4 p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm">{{ session('warning') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
            @endif

            <!-- Filter status -->
            <div class="mb-4 flex gap-2 text-xs">
                <a href="{{ route('discipline-cases.index') }}" class="px-3 py-1.5 rounded-full {{ !$status?'bg-emerald-100 text-emerald-700 font-semibold':'bg-gray-100 text-gray-600' }}">Semua</a>
                <a href="?status=found" class="px-3 py-1.5 rounded-full {{ $status==='found'?'bg-blue-100 text-blue-700 font-semibold':'bg-gray-100 text-gray-600' }}">Found</a>
                <a href="?status=validated" class="px-3 py-1.5 rounded-full {{ $status==='validated'?'bg-emerald-100 text-emerald-700 font-semibold':'bg-gray-100 text-gray-600' }}">Validated</a>
                <a href="?status=dismissed" class="px-3 py-1.5 rounded-full {{ $status==='dismissed'?'bg-gray-200 text-gray-600 font-semibold':'bg-gray-100 text-gray-600' }}">Dismissed</a>
                <a href="?status=done" class="px-3 py-1.5 rounded-full {{ $status==='done'?'bg-gray-100 text-gray-600 font-semibold':'bg-gray-100 text-gray-600' }}">Done</a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">No. Kasus</th>
                                <th class="px-4 py-3 text-left font-semibold">Siswa</th>
                                <th class="px-4 py-3 text-left font-semibold">Pelanggaran</th>
                                <th class="px-4 py-3 text-center font-semibold">Poin</th>
                                <th class="px-4 py-3 text-center font-semibold">Status</th>
                                <th class="px-4 py-3 text-left font-semibold">Dilaporkan</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($cases as $case)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-xs">{{ $case->case_number }}</td>
                                    <td class="px-4 py-3">{{ $case->student?->full_name ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $case->violationCategory?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center font-semibold">{{ $case->violationCategory?->points ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @php $statusColors = ['found'=>'bg-blue-100 text-blue-700','validated'=>'bg-emerald-100 text-emerald-700','dismissed'=>'bg-gray-100 text-gray-600','done'=>'bg-gray-200 text-gray-700']; @endphp
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusColors[$case->status] ?? '' }}">{{ $case->status }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ $case->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-right space-x-2 text-xs">
                                        @if($case->status==='found')
                                            <form action="{{ route('discipline-cases.validate', $case) }}" method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="validation_passed" value="1">
                                                <button class="text-emerald-600 hover:underline font-semibold">Validasi</button>
                                            </form>
                                            <form action="{{ route('discipline-cases.validate', $case) }}" method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="validation_passed" value="0">
                                                <button class="text-gray-500 hover:underline">Tolak</button>
                                            </form>
                                        @endif
                                        @if($case->status==='validated')
                                            <form action="{{ route('discipline-cases.done', $case) }}" method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <button class="text-blue-600 hover:underline font-semibold">Selesai</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">Belum ada kasus. Klik "Lapor Kasus" untuk memulai.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">{{ $cases->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
