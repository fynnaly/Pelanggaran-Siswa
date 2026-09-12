<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Siswa: {{ $student->full_name }}</h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if($errors->any())
                    <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                        @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
                    </div>
                @endif
                <form action="{{ route('students.update', $student) }}" method="POST" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">NISN</label>
                        <input type="text" name="nisn" value="{{ old('nisn', $student->nisn) }}" required maxlength="20"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">NIS</label>
                        <input type="text" name="nis" value="{{ old('nis', $student->nis) }}" required maxlength="20"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="full_name" value="{{ old('full_name', $student->full_name) }}" required maxlength="100"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                    @php $klases = \App\Models\SchoolClass::all(); @endphp
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Kelas</label>
                        <select name="class_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">-- Pilih kelas --</option>
                            @foreach($klases as $k)
                                <option value="{{ $k->id }}" {{ old('class_id', $student->class_id)==$k->id?'selected':'' }}>{{ $k->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="active" {{ old('status', $student->status)==='active'?'selected':'' }}>Active</option>
                            <option value="inactive" {{ old('status', $student->status)==='inactive'?'selected':'' }}>Inactive</option>
                            <option value="graduated" {{ old('status', $student->status)==='graduated'?'selected':'' }}>Graduated</option>
                            <option value="transferred" {{ old('status', $student->status)==='transferred'?'selected':'' }}>Transferred</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3 pt-2">
                        <a href="{{ route('students.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Batal</a>
                        <button class="px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
