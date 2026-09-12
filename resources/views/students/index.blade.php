<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daftar Siswa</h2>
            <a href="{{ route('students.create') }}" class="px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700">+ Tambah Siswa</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ $errors->first() }}</div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">NISN</th>
                                <th class="px-4 py-3 text-left font-semibold">NIS</th>
                                <th class="px-4 py-3 text-left font-semibold">Nama</th>
                                <th class="px-4 py-3 text-left font-semibold">Kelas</th>
                                <th class="px-4 py-3 text-center font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($students as $student)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-xs">{{ $student->nisn }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $student->nis }}</td>
                                    <td class="px-4 py-3">{{ $student->full_name }}</td>
                                    <td class="px-4 py-3">{{ $student->schoolClass?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center"><span class="px-2 py-1 rounded-full text-xs {{ $student->status==='active'?'bg-emerald-100 text-emerald-700':'bg-gray-100 text-gray-600' }}">{{ $student->status }}</span></td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <a href="{{ route('students.edit', $student) }}" class="text-blue-600 hover:underline text-xs">Edit</a>
                                        <form action="{{ route('students.destroy', $student) }}" method="POST" class="inline" onsubmit="return confirm('Hapus siswa?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:underline text-xs">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">Belum ada siswa. Tambah dulu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">{{ $students->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>