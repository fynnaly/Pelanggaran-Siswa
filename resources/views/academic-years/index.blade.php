<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daftar Tahun Ajaran</h2>
            <a href="{{ route('academic-years.create') }}" class="px-4 py-2 bg-tertiary text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition">Tambah Tahun Ajaran</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Tahun Ajaran</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($academicYears as $year)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $year->name }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-3">
                                            <a href="{{ route('academic-years.edit', $year) }}" class="text-tertiary hover:text-secondary text-xs font-semibold">Edit</a>
                                            <form action="{{ route('academic-years.destroy', $year) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus tahun ajaran {{ $year->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-danger hover:text-800 text-xs font-semibold">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-10 text-center text-gray-500">Belum ada data tahun ajaran. Silakan tambah data baru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- Pagination gunakan variabel yang bener --}}
                @if($academicYears->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $academicYears->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>