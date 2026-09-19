<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Kelas</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg p-6">
                @if($errors->any())
                    <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                        @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
                    </div>
                @endif
                <form action="{{ route('classes.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tahun Ajaran</label>
                        <select name="academic_year_id" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-tertiary focus:border-tertiary">
                            <option value="">-- Pilih tahun --</option>
                            @foreach($academicYears as $id => $name)
                                <option value="{{ $id }}" {{ old('academic_year_id')==$id?'selected':'' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Kelas</label>
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="20" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-tertiary focus:border-tertiary">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Wali Kelas</label>
                        <select name="homeroom_teacher_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-tertiary focus:border-tertiary">
                            <option value="">-- Pilih wali --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('homeroom_teacher_id')==$user->id?'selected':'' }}>{{ $user->name }}</option>
                            @endforeach
                            @if($users->isEmpty())
                                <option value="" disabled>Tidak ada user tersedia</option>
                            @endif
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3 pt-2">
                        <a href="{{ route('classes.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Batal</a>
                        <button class="px-4 py-2 bg-tertiary text-white text-sm font-semibold rounded-lg hover:bg-emerald-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
