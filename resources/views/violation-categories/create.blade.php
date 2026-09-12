<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Kategori Pelanggaran</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if($errors->any())
                    <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                        @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
                    </div>
                @endif
                <form action="{{ route('violation-categories.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Kode</label>
                        <input type="text" name="code" value="{{ old('code') }}" required maxlength="20"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama</label>
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="150"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tingkat</label>
                            <select name="severity" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="ringan" {{ old('severity')==='ringan'?'selected':'' }}>Ringan</option>
                                <option value="sedang" {{ old('severity')==='sedang'?'selected':'' }}>Sedang</option>
                                <option value="berat" {{ old('severity')==='berat'?'selected':'' }}>Berat</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Poin</label>
                            <input type="number" name="points" value="{{ old('points', 10) }}" required min="1"
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                        <select name="status" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="draft" {{ old('status')==='draft'?'selected':'' }}>Draft</option>
                            <option value="active" {{ old('status')==='active'?'selected':'' }}>Active</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3 pt-2">
                        <a href="{{ route('violation-categories.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Batal</a>
                        <button class="px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
