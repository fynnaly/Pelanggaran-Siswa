<x-app-layout>
    <x-slot name="header">

        <div class="page-header">
            <div>
                <h1>Tambah Siswa</h1>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('students.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card card-form">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
            <form action="{{ route('students.store') }}" method="POST">
                @csrf
                <div class="field"><label class="field-label">NISN <span class="text-danger">*</span></label><input type="text" name="nisn" value="{{ old('nisn') }}" class="field-input" required maxlength="20" placeholder="Nomor Induk Siswa Nasional"></div>
                <div class="field"><label class="field-label">NIS <span class="text-danger">*</span></label><input type="text" name="nis" value="{{ old('nis') }}" class="field-input" required maxlength="20" placeholder="Nomor Induk Siswa"></div>
                <div class="field"><label class="field-label">Nama Lengkap <span class="text-danger">*</span></label><input type="text" name="full_name" value="{{ old('full_name') }}" class="field-input" required maxlength="255"></div>
                <div class="field"><label class="field-label">Jenis Kelamin <span class="text-danger">*</span></label>
                    <select name="gender" class="field-input" required>
                        <option value="">Pilih...</option>
                        <option value="L" {{ old('gender')==='L'?'selected':'' }}>Laki-laki</option>
                        <option value="P" {{ old('gender')==='P'?'selected':'' }}>Perempuan</option>
                    </select>
                </div>
                <div class="field"><label class="field-label">Tanggal Lahir</label><input type="date" name="birth_date" value="{{ old('birth_date') }}" class="field-input"></div>
                <div class="field"><label class="field-label">Tahun Ajaran <span class="text-danger">*</span></label>
                    <select name="academic_year_id" class="field-input" required>
                        <option value="">Pilih tahun ajaran...</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ old('academic_year_id')==$year->id?'selected':'' }}>{{ $year->name }}{{ $year->is_active?' (Aktif)':'' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="field-label">Kelas <span class="text-danger">*</span></label>
                    <select name="class_id" class="field-input" required>
                        <option value="">Pilih kelas...</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ old('class_id')==$class->id?'selected':'' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="field-label">No. HP Siswa</label><input type="text" name="phone" value="{{ old('phone') }}" class="field-input" maxlength="20" placeholder="Opsional"></div>
                <div class="field"><label class="field-label">Alamat</label><textarea name="address" class="field-input" placeholder="Opsional">{{ old('address') }}</textarea></div>
                <div class="field"><label class="field-label">Nama Orang Tua</label><input type="text" name="parent_name" value="{{ old('parent_name') }}" class="field-input" maxlength="100" placeholder="Opsional"></div>
                <div class="field"><label class="field-label">No. HP Orang Tua</label><input type="text" name="parent_phone" value="{{ old('parent_phone') }}" class="field-input" maxlength="20" placeholder="Opsional"></div>
                <div class="field"><label class="field-label">Status</label>
                    <select name="status" class="field-input">
                        <option value="active" {{ old('status')==='active'?'selected':'' }}>Aktif</option>
                        <option value="inactive" {{ old('status')==='inactive'?'selected':'' }}>Tidak Aktif</option>
                        <option value="graduated" {{ old('status')==='graduated'?'selected':'' }}>Lulus</option>
                        <option value="transferred" {{ old('status')==='transferred'?'selected':'' }}>Pindah</option>
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
