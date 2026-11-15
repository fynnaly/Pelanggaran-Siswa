<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Tambah Kelas</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('classes.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <x-prerequisite-modal
            :has-prerequisites="$hasPrerequisites"
            title="Tahun Ajaran Belum Tersedia"
            message="Pastikan Tahun Ajaran aktif sudah dibuat terlebih dahulu sebelum menambahkan data kelas."
            :prerequisites="$prerequisites"
        />

        <div class="card card-form">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
            <form action="{{ route('classes.store') }}" method="POST">
                @csrf
                <div class="field"><label class="field-label">Tahun Ajaran <span class="text-danger">*</span></label>
                    <select name="academic_year_id" class="field-input" required>
                        <option value="">Pilih tahun ajaran...</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ old('academic_year_id')==$year->id?'selected':'' }}>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="field-label">Tingkatan <span class="text-danger">*</span></label>
                    <select name="grade_level" class="field-input" id="gradeLevel" required>
                        <option value="">Pilih tingkatan...</option>
                        <option value="X" {{ old('grade_level')==='X'?'selected':'' }}>X</option>
                        <option value="XI" {{ old('grade_level')==='XI'?'selected':'' }}>XI</option>
                        <option value="XII" {{ old('grade_level')==='XII'?'selected':'' }}>XII</option>
                    </select>
                </div>
                <div class="field"><label class="field-label">Jurusan <span class="text-danger">*</span></label>
                    <select name="department_id" class="field-input" id="departmentSelect" required>
                        <option value="">Pilih jurusan...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" data-code="{{ $dept->code }}" {{ old('department_id')==$dept->id?'selected':'' }}>{{ $dept->code }} — {{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <span class="help-text">Diambil dari data referensi Jurusan.</span>
                </div>
                <div class="field"><label class="field-label">Nomor <span class="text-danger">*</span></label>
                    <input type="number" name="number" value="{{ old('number', 1) }}" min="1" max="99" class="field-input" id="classNumber" required>
                    <span class="help-text">Pembeda antar rombel satu jurusan. Kalau jurusannya cuma satu, isi 1 saja.</span>
                </div>
                <div class="field"><label class="field-label">Nama Kelas</label>
                    <input type="text" id="composedName" class="field-input cursor-not-allowed" value="{{ old('name') }}" readonly style="background:var(--surface);color:var(--on-surface-muted)">
                    <span class="help-text">Nama kelas tersusun otomatis: Tingkatan + Jurusan + Nomor (mis. X PPLG 1).</span>
                </div>
                <div class="field"><label class="field-label">Status</label>
                    <input type="text" class="field-input cursor-not-allowed" value="Aktif" disabled style="background:var(--surface);cursor:not-allowed">
                    <input type="hidden" name="status" value="active">
                    <span class="help-text">Kelas baru otomatis terdaftar dalam status aktif</span>
                </div>
                <div class="field"><label class="field-label">Wali Kelas</label>
                    <select name="homeroom_teacher_id" class="field-input">
                        <option value="">Belum ditentukan</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('homeroom_teacher_id')==$user->id?'selected':'' }}>{{ $user->name }}</option>
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            // Nama kelas tersusun live: Tingkatan + Kode Jurusan + Nomor
            const compose = () => {
                const grade = document.getElementById('gradeLevel')?.value || '';
                const code = document.getElementById('departmentSelect')?.selectedOptions[0]?.dataset.code || '';
                const num = document.getElementById('classNumber')?.value || '';
                const name = [grade, code, num].filter(Boolean).join(' ');
                const out = document.getElementById('composedName');
                if (out) out.value = name || '';
            };
            ['gradeLevel', 'departmentSelect', 'classNumber'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('change', compose);
            });
            compose();
        });
    </script>
</x-app-layout>
