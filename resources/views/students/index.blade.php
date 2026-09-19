<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daftar Siswa</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('students.template') }}" class="px-3 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200">Template</a>
                <a href="{{ route('students.export') }}" class="px-3 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700">Export</a>
                <a href="{{ route('students.create') }}" class="px-4 py-2 bg-tertiary text-white text-sm font-semibold rounded-lg hover:bg-emerald-700">Tambah Siswa</a>
            </div>
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

            {{-- Filter badge --}}
            @if($classId)
                <div class="mb-4 flex items-center gap-2">
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-semibold rounded-full">Filter: Kelas {{ $students->first()?->schoolClass?->name ?? '#' . $classId }}</span>
                    <a href="{{ route('students.index') }}" class="text-xs text-gray-500 hover:text-gray-700">Hapus filter</a>
                </div>
            @endif

            {{-- Search + Import --}}
            <div class="flex flex-col sm:flex-row gap-3 mb-4">
                <form action="{{ route('students.index') }}" method="GET" class="flex-1 flex gap-2">
                    @if($classId)
                        <input type="hidden" name="class_id" value="{{ $classId }}">
                    @endif
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / NISN / NIS..." class="flex-1 border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                    <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200">Cari</button>
                </form>

                <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-3 py-2 shadow-sm">
                    @csrf
                    <label class="text-xs text-gray-500 font-semibold whitespace-nowrap">Import CSV:</label>
                    <input type="file" name="file" accept=".csv,.txt" required class="text-xs border-gray-300 rounded focus:ring-emerald-500 focus:border-emerald-500">
                    <button type="submit" class="px-3 py-1 bg-emerald-600 text-white text-xs font-semibold rounded hover:bg-emerald-700">Unggah</button>
                </form>
            </div>

            {{-- Bulk promote form --}}
            <form action="{{ route('classes.promote') }}" method="POST" id="bulkPromoteForm" class="mb-4 hidden">
                @csrf
                <div class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-4 py-3 shadow-sm">
                    <span class="text-sm text-gray-700 font-semibold" id="selectedCount">0</span>
                    <span class="text-sm text-gray-500">siswa dipilih</span>
                    <span class="text-gray-300">|</span>
                    <label class="text-sm text-gray-700 font-semibold">Pindah ke:</label>
                    <select name="class_id" required class="border-gray-300 rounded-lg shadow-sm text-sm focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">Pilih kelas...</option>
                        @foreach($classesForPromote as $id => $name)
                            @if($classId && $id == $classId)
                                @continue
                            @endif
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700">Naik Kelas</button>
                </div>
            </form>

            {{-- Tabel siswa --}}
            <div class="bg-white rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-center w-10">
                                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                </th>
                                <th class="px-4 py-3 text-left font-semibold">NISN</th>
                                <th class="px-4 py-3 text-left font-semibold">NIS</th>
                                <th class="px-4 py-3 text-left font-semibold">Nama</th>
                                <th class="px-4 py-3 text-left font-semibold">Kelas</th>
                                <th class="px-4 py-3 text-center font-semibold">Status</th>
                                <th class="px-4 py-3 text-center font-semibold">Poin</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($students as $student)
                                @php
                                    $poin = $student->pointLedgers->first()?->balance_after ?? 2000;
                                    $poinColor = $poin <= 500 ? 'text-red-700 bg-red-50'
                                                 : ($poin <= 1000 ? 'text-amber-700 bg-amber-50'
                                                 : 'text-emerald-700 bg-emerald-50');
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-cb rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $student->nisn }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $student->nis }}</td>
                                    <td class="px-4 py-3 font-semibold text-gray-900">{{ $student->full_name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $student->schoolClass?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                                            {{ $student->status==='active' ? 'bg-emerald-100 text-emerald-700'
                                               : ($student->status==='graduated' ? 'bg-blue-100 text-blue-700'
                                               : ($student->status==='transferred' ? 'bg-purple-100 text-purple-700'
                                               : 'bg-gray-100 text-gray-600')) }}">
                                            {{ ucfirst($student->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 rounded-lg text-xs font-bold {{ $poinColor }}">{{ $poin }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <a href="{{ route('students.edit', $student) }}" class="text-blue-600 hover:underline text-xs font-semibold">Edit</a>
                                        <form action="{{ route('students.destroy', $student) }}" method="POST" class="inline" onsubmit="return confirm('Hapus siswa {{ $student->full_name }}?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:underline text-xs font-semibold">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                                        @if(request('q'))
                                            Tidak ada siswa yang cocok dengan pencarian "{{ request('q') }}".
                                        @else
                                            Belum ada siswa. Tambahkan atau import dari CSV.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">{{ $students->links() }}</div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.student-cb');
            const form = document.getElementById('bulkPromoteForm');
            const counter = document.getElementById('selectedCount');

            function updateSelection() {
                const checked = document.querySelectorAll('.student-cb:checked').length;
                counter.textContent = checked;
                form.classList.toggle('hidden', checked === 0);
            }

            selectAll?.addEventListener('change', () => {
                checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
                updateSelection();
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateSelection);
            });
        });
    </script>
</x-app-layout>
