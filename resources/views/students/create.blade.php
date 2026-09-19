<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Siswa / Tambah</div>
        <div class="page-header"><h1>Tambah Siswa</h1></div>
    </x-slot>
    <div class="main-wrap">
        <div class="card" style="max-width:600px">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
            <form action="{{ route('students.store') }}" method="POST">
                @csrf
                <div class="field">
                    <label class="field-label">NISN <span class="text-danger">*</span></label>
                    <input type="text" name="nisn" value="{{ old('nisn') }}" required maxlength="20" class="field-input" placeholder="Nomor Induk Siswa Nasional">
                </div>
                <div class="field">
                    <label class="field-label">NIS <span class="text-danger">*</span></label>
                    <input type="text" name="nis" value="{{ old('nis') }}" required maxlength="20" class="field-input" placeholder="Nomor Induk Siswa">
                </div>
                <div class="field">
                    <label class="field-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" required maxlength="100" class="field-input" placeholder="Nama lengkap siswa">
                </div>
                <div class="field">
                    <label class="field-label">Kelas <span class="text-danger">*</span></label>
                    <select name="class_id" required class="field-input">
                        <option value="">-- Pilih kelas --</option>
                        @foreach($klases as $k)<option value="{{ $k->id }}" {{ old('class_id')==(string)$k->id?'selected':'' }}>{{ $k->name }}{{ $k->academicYear ? ' ('.$k->academicYear->name.')' : '' }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="field-label">Status <span class="text-danger">*</span></label>
                    <select name="status" required class="field-input">
                        <option value="active" {{ old('status')==='active'?'selected':'' }}>Active</option>
                        <option value="inactive" {{ old('status')==='inactive'?'selected':'' }}>Inactive</option>
                        <option value="graduated" {{ old('status')==='graduated'?'selected':'' }}>Graduated</option>
                        <option value="transferred" {{ old('status')==='transferred'?'selected':'' }}>Transferred</option>
                    </select>
                </div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('students.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>