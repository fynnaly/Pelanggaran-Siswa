<x-app-layout>
    <x-slot name="header">

        <div class="page-header">
            <div>
                <h1>Edit: {{ $student->full_name }}</h1>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('students.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card card-form">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
            <form action="{{ route('students.update', $student) }}" method="POST">
                @csrf @method('PUT')
                <div class="field"><label class="field-label">NISN <span class="text-danger">*</span></label><input type="text" name="nisn" value="{{ old('nisn', $student->nisn) }}" class="field-input" required maxlength="10" placeholder="10 digit angka"></div>
                <div class="field"><label class="field-label">NIS <span class="text-danger">*</span></label><input type="text" name="nis" value="{{ old('nis', $student->nis) }}" class="field-input" required maxlength="12" placeholder="12 digit: YYYYMMDD + urutan 4"></div>
                <div class="field"><label class="field-label">Nama Lengkap <span class="text-danger">*</span></label><input type="text" name="full_name" value="{{ old('full_name', $student->full_name) }}" class="field-input" required maxlength="255"></div>
                <div class="field"><label class="field-label">Jenis Kelamin <span class="text-danger">*</span></label>
                    <div style="display:flex;gap:var(--sp-lg)">
                        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="radio" name="gender" value="L" required {{ old('gender', $student->gender)==='L'?'checked':'' }} style="width:16px;height:16px;accent-color:var(--primary)"> Laki-laki
                        </label>
                        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="radio" name="gender" value="P" required {{ old('gender', $student->gender)==='P'?'checked':'' }} style="width:16px;height:16px;accent-color:var(--primary)"> Perempuan
                        </label>
                    </div>
                </div>
                <div class="field"><label class="field-label">Tanggal Lahir</label><input type="date" name="birth_date" value="{{ old('birth_date', $student->birth_date?->format('Y-m-d')) }}" class="field-input"></div>
                <div class="field"><label class="field-label">Tahun Ajaran <span class="text-danger">*</span></label>
                    <select name="academic_year_id" class="field-input" required>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ old('academic_year_id', $student->latestEnrollment()?->academic_year_id)==$year->id?'selected':'' }}>{{ $year->name }}{{ $year->is_active?' (Aktif)':'' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="field-label">Kelas <span class="text-danger">*</span></label>
                    <select name="class_id" class="field-input" required>
                        <option value="">Pilih kelas...</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ old('class_id', $student->latestEnrollment()?->class_id)==$class->id?'selected':'' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="field-label">No. HP Siswa</label><input type="text" name="phone" value="{{ old('phone', $student->phone) }}" class="field-input" maxlength="20" placeholder="Opsional"></div>
                <div class="field"><label class="field-label">Alamat</label><textarea name="address" class="field-input" placeholder="Opsional">{{ old('address', $student->address) }}</textarea></div>
                <div class="field"><label class="field-label">Nama Orang Tua</label><input type="text" name="parent_name" value="{{ old('parent_name', $student->parent_name) }}" class="field-input" maxlength="100" placeholder="Opsional"></div>
                <div class="field"><label class="field-label">No. HP Orang Tua</label><input type="text" name="parent_phone" value="{{ old('parent_phone', $student->parent_phone) }}" class="field-input" maxlength="20" placeholder="Opsional"></div>
                <div class="field"><label class="field-label">Status</label>
                    <select name="status" class="field-input">
                        <option value="active" {{ old('status', $student->status)==='active'?'selected':'' }}>Aktif</option>
                        <option value="inactive" {{ old('status', $student->status)==='inactive'?'selected':'' }}>Tidak Aktif</option>
                        <option value="graduated" {{ old('status', $student->status)==='graduated'?'selected':'' }}>Lulus</option>
                        <option value="transferred" {{ old('status', $student->status)==='transferred'?'selected':'' }}>Pindah</option>
                    </select>
                </div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('students.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="save" class="icon-sm"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
