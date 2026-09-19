<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Kelas / Edit</div>
        <div class="page-header"><h1>Edit Kelas: {{ $class->name }}</h1></div>
    </x-slot>
    <div class="main-wrap">
        <div class="card" style="max-width:600px">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
            <form action="{{ route('classes.update', $class) }}" method="POST">
                @csrf @method('PUT')
                <div class="field">
                    <label class="field-label">Tahun Ajaran</label>
                    <select name="academic_year_id" class="field-input">
                        <option value="">-- Pilih tahun --</option>
                        @foreach($academicYears as $id => $name)<option value="{{ $id }}" {{ old('academic_year_id', $class->academic_year_id)==$id?'selected':'' }}>{{ $name }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="field-label">Nama Kelas <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $class->name) }}" required maxlength="20" class="field-input">
                </div>
                <div class="field">
                    <label class="field-label">Wali Kelas</label>
                    <select name="homeroom_teacher_id" class="field-input">
                        <option value="">-- Pilih wali --</option>
                        @foreach(\\App\\Models\\User::whereHas('roles', fn($q) => $q->where('name','guru'))->get() as $user)
                            <option value="{{ $user->id }}" {{ old('homeroom_teacher_id', $class->homeroom_teacher_id)==$user->id?'selected':'' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('classes.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
