<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daftar Kelas</h2>
            <a href="{{ route('classes.create') }}" class="px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700">+ Tambah Kelas</a>
        </div>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Nama Kelas</th>
                                <th class="px-4 py-3 text-left font-semibold">Tahun Ajaran</th>
                                <th class="px-4 py-3 text-center font-semibold">Jumlah Siswa</th>
                                <th class="px-4 py-3 text-left font-semibold">Wali Kelas</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($classes as $class)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-semibold">{{ $class->name }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600">{{ $class->academicYear?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center font-semibold">{{ $class->students()->count() }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600">{{ $class->homeroomTeacher?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <a href="{{ route('classes.edit', $class) }}" class="text-blue-600 hover:underline text-xs">Edit</a>
                                        <form action="{{ route('classes.destroy', $class) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kelas ini?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:underline text-xs">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">Belum ada kelas. Tambah dulu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">{{ $classes->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
