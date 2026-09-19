<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Siswa / Edit</div>
        <div class="page-header"><h1>Edit: {{ $student->full_name }}</h1></div>
    </x-slot>
    <div class="main-wrap">
        <div class="card" style="max-width:600px">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
            <form action="{{ route('students.update', $student) }}" method="POST">
                @csrf @method('PUT')
                <div class="field">
                    <label class="field-label">NISN <span class="text-danger">*</span></label>
                    <input type="text" name="nisn" value="{{ old('nisn', $student->nisn) }}" required maxlength="20" class="field-input">
                </div>
                <div class="field">
                    <label class="field-label">NIS <span class="text-danger">*</span></label>
                    <input type="text" name="nis" value="{{ old('nis', $student->nis) }}" required maxlength="20" class="field-input">
                </div>
                <div class="field">
                    <label class="field-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" value="{{ old('full_name', $student->full_name) }}" required maxlength="100" class="field-input">
                </div>
                @php $klases = \App\Models\SchoolClass::all(); @endphp
                <div class="field">
                    <label class="field-label">Kelas</label>
                    <select name="class_id" class="field-input">
                        <option value="">-- Pilih kelas --</option>
                        @foreach($klases as $k)<option value="{{ $k->id }}" {{ old('class_id', $student->class_id)==$k->id?'selected':'' }}>{{ $k->name }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="field-label">Status</label>
                    <select name="status" class="field-input">
                        <option value="active" {{ old('status', $student->status)==='active'?'selected':'' }}>Active</option>
                        <option value="inactive" {{ old('status', $student->status)==='inactive'?'selected':'' }}>Inactive</option>
                        <option value="graduated" {{ old('status', $student->status)==='graduated'?'selected':'' }}>Graduated</option>
                        <option value="transferred" {{ old('status', $student->status)==='transferred'?'selected':'' }}>Transferred</option>
                    </select>
                </div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('students.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>