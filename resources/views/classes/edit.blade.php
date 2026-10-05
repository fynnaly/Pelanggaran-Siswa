<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Edit: {{ $class->name }}</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('classes.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card card-form">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
            <form action="{{ route('classes.update', $class) }}" method="POST">
                @csrf @method('PUT')
                <div class="field"><label class="field-label">Tahun Ajaran <span class="text-danger">*</span></label>
                    <select name="academic_year_id" class="field-input" required>
                        <option value="">Pilih tahun ajaran...</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ old('academic_year_id', $class->academic_year_id)==$year->id?'selected':'' }}>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="field-label">Nama Kelas <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $class->name) }}" class="field-input" required>
                </div>
                <div class="field"><label class="field-label">Tingkat</label>
                    <select name="grade_level" class="field-input">
                        <option value="">Pilih tingkat...</option>
                        <option value="X" {{ old('grade_level', $class->grade_level)==='X'?'selected':'' }}>X/10</option>
                        <option value="XI" {{ old('grade_level', $class->grade_level)==='XI'?'selected':'' }}>XI/11</option>
                        <option value="XII" {{ old('grade_level', $class->grade_level)==='XII'?'selected':'' }}>XII/12</option>
                    </select>
                </div>
                <div class="field"><label class="field-label">Program Keahlian</label>
                    <input type="text" name="major" value="{{ old('major', $class->major) }}" class="field-input" placeholder="Contoh: RPL">
                </div>
                <div class="field"><label class="field-label">Wali Kelas</label>
                    <select name="homeroom_teacher_id" class="field-input">
                        <option value="">Belum ditentukan</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('homeroom_teacher_id', $class->homeroom_teacher_id)==$user->id?'selected':'' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('classes.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="save" class="icon-sm"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
