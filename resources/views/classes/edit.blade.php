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
        @php
            // Rombel diambil dari bagian akhir nama kelas yang tersimpan (huruf).
            $classRombel = old('rombel', $class->rombel ?: 'A');
            $yearStartYears = $academicYears->mapWithKeys(fn ($year) => [$year->id => $year->startYear()]);
        @endphp
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
                <div class="field"><label class="field-label">Tingkatan <span class="text-danger">*</span></label>
                    <select name="grade_level" class="field-input" id="gradeLevel" required>
                        <option value="">Pilih tingkatan...</option>
                        @foreach(['VII' => 'VII/7', 'VIII' => 'VIII/8', 'IX' => 'IX/9'] as $value => $label)
                            <option value="{{ $value }}" {{ old('grade_level', $class->grade_level)===$value?'selected':'' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="field-label">Rombel <span class="text-danger">*</span></label>
                    <input type="text" name="rombel" value="{{ $classRombel }}" maxlength="2" pattern="[A-Za-z]{1,2}" class="field-input" id="classRombel" placeholder="A" autocomplete="off" required>
                    <span class="help-text">Hanya huruf, mis. A atau B.</span>
                </div>
                <div class="field"><label class="field-label">Nama Kelas</label>
                    <input type="text" id="composedName" class="field-input cursor-not-allowed" value="{{ old('name', $class->name) }}" readonly style="background:var(--surface);color:var(--on-surface-muted)">
                    <span class="help-text">Nama kelas tersusun otomatis: Tingkatan + Rombel (mis. VII A).</span>
                </div>
                <div class="field"><label class="field-label">Angkatan</label>
                    <input type="text" id="angkatanField" class="field-input cursor-not-allowed" value="{{ $class->angkatan }}" readonly style="background:var(--surface);color:var(--on-surface-muted)">
                    <span class="help-text">Tahun masuk siswa, dihitung otomatis dari tahun ajaran + tingkatan.</span>
                </div>
                <div class="field"><label class="field-label">Status</label>
                    <select name="status" class="field-input">
                        <option value="active" {{ old('status', $class->status ?? 'active')==='active'?'selected':'' }}>Aktif</option>
                        <option value="inactive" {{ old('status', $class->status ?? 'active')==='inactive'?'selected':'' }}>Nonaktif</option>
                    </select>
                    <span class="help-text">Kelas tahun berjalan = Aktif. Kelas tahun lalu (riwayat) = Nonaktif.</span>
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            const rombel = document.getElementById('classRombel');

            // Rombel hanya boleh huruf; normalisasi langsung saat diketik.
            rombel?.addEventListener('input', () => {
                rombel.value = rombel.value.replace(/[^a-zA-Z]/g, '').toUpperCase().slice(0, 2);
                compose();
            });

            // Nama kelas tersusun live: Tingkatan + Rombel.
            function compose() {
                const grade = document.getElementById('gradeLevel')?.value || '';
                const rombelValue = (rombel?.value || '').trim().toUpperCase();
                const name = [grade, rombelValue].filter(Boolean).join(' ');
                const out = document.getElementById('composedName');
                if (out) out.value = name;

                // Angkatan: tahun awal tahun ajaran terpilih dikurangi offset tingkatan.
                const yearStart = @json($yearStartYears);
                const yearId = document.querySelector('select[name="academic_year_id"]')?.value || '';
                const startYear = yearStart[yearId] ?? null;
                const offset = { VII: 0, VIII: 1, IX: 2 }[grade] ?? 0;
                const angkatan = document.getElementById('angkatanField');
                if (angkatan) angkatan.value = startYear ? (startYear - offset) : '';
            }

            document.getElementById('gradeLevel')?.addEventListener('change', compose);
            document.querySelector('select[name="academic_year_id"]')?.addEventListener('change', compose);
            compose();
        });
    </script>
</x-app-layout>
