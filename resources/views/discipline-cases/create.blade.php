<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lapor Pelanggaran Baru</h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if($errors->any())
                    <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                        @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
                    </div>
                @endif
                <form action="{{ route('discipline-cases.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Siswa</label>
                        <select name="student_id" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">-- Pilih siswa --</option>
                            @foreach($students as $id => $name)
                                <option value="{{ $id }}" {{ old('student_id')==$id?'selected':'' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Kategori Pelanggaran</label>
                        <select name="violation_category_id" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">-- Pilih kategori --</option>
                            @foreach($categories as $id => $name)
                                <option value="{{ $id }}" {{ old('violation_category_id')==$id?'selected':'' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Dilaporkan oleh (user_id)</label>
                        <input type="number" name="report_by" value="{{ auth()->id() }}" required
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Lokasi (opsional)</label>
                        <input type="text" name="location" value="{{ old('location') }}" maxlength="100" placeholder="Contoh: Kantin, Kelas X-1"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi</label>
                        <textarea name="description" required rows="3" placeholder="Jelaskan kronologi pelanggaran..."
                                  class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">{{ old('description') }}</textarea>
                    </div>
                    <input type="hidden" name="status" value="found">
                    <div class="flex justify-end space-x-3 pt-2">
                        <a href="{{ route('discipline-cases.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Batal</a>
                        <button class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700">Catat Kasus (status: found)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
