<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Tahun Ajaran Baru</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg p-6">
                <form action="{{ route('academic-years.store') }}" method="POST">
                    @csrf

                    <!--Input Nama Tahun Ajaran -->
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nama Tahun Ajaran</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: 2026/2027" required>
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Input Tanggal Mulai -->
                    <div class="mb-4">
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                        @error('start_date')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Checkbox Status Aktif -->
                    <div class="mb-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-tertiary shadow-sm focus:border-emerald-500 focus:ring-emerald-500" {{ old('is_active') ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700">Jadikan Tahun Ajaran Ini Aktif (Default)</span>
                        </label>
                        <p id="is_active-help" class="mt-1 flex items-start gap-1.5 text-xs text-gray-500">
                            <svg class="h-3.5 w-3.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Jika diaktifkan, tahun ajaran lain yang aktif akan otomatis dinonaktifkan.</span>
                        </p>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('academic-years.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-300 transition">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-tertiary text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition">Simpan Tahun Ajaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>