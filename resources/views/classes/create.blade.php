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
                        @foreach(['VII' => 'VII/7', 'VIII' => 'VIII/8', 'IX' => 'IX/9'] as $value => $label)
                            <option value="{{ $value }}" {{ old('grade_level')===$value?'selected':'' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="field-label">Rombel <span class="text-danger">*</span></label>
                    <input type="text" name="rombel" value="{{ old('rombel', '1') }}" maxlength="2" class="field-input" id="classRombel" placeholder="1 atau A" required>
                    <span class="help-text">Pembeda antar kelas pada tingkatan yang sama. Boleh angka (1, 2) atau huruf (A, B).</span>
                </div>
                <div class="field"><label class="field-label">Nama Kelas</label>
                    <input type="text" id="composedName" class="field-input cursor-not-allowed" value="{{ old('name') }}" readonly style="background:var(--surface);color:var(--on-surface-muted)">
                    <span class="help-text">Nama kelas tersusun otomatis: Tingkatan + Rombel (mis. VII 1 atau VII A).</span>
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
            // Nama kelas tersusun live: Tingkatan + Rombel (angka atau huruf).
            const compose = () => {
                const grade = document.getElementById('gradeLevel')?.value || '';
                const rombel = (document.getElementById('classRombel')?.value || '').trim().toUpperCase();
                const name = [grade, rombel].filter(Boolean).join(' ');
                const out = document.getElementById('composedName');
                if (out) out.value = name;
            };
            ['gradeLevel', 'classRombel'].forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', compose);
            });
            compose();
        });
    </script>
</x-app-layout>
