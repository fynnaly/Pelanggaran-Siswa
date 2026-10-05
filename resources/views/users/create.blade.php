<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Tambah Pengguna</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card card-form">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="field"><label class="field-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="field-input" required>
                </div>
                <div class="field"><label class="field-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" class="field-input" required>
                </div>
                <div class="field"><label class="field-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="field-input" minlength="8" required>
                    <span class="help-text">Minimal 8 karakter.</span>
                </div>
                <div class="field"><label class="field-label">Role / Jabatan <span class="text-danger">*</span></label>
                    <select name="role" id="roleSelectCreate" class="field-input" required>
                        <option value="">Pilih role...</option>
                        @foreach($roles as $r)
                            <option value="{{ $r }}" {{ old('role')===$r?'selected':'' }}>{{ $r }}</option>
                        @endforeach
                    </select>
                    <span class="help-text">Untuk guru: guru, bk, kesiswaan, kepala_sekolah, admin. Untuk siswa: siswa.</span>
                </div>
                <div class="field" id="studentFieldCreate"><label class="field-label">Identitas Siswa</label>
                    <select name="student_id" class="field-input">
                        <option value="">Tidak terkait</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}" {{ old('student_id')==$s->id?'selected':'' }}>{{ $s->full_name }} ({{ $s->nis }} / {{ $s->nisn }})</option>
                        @endforeach
                    </select>
                    <span class="help-text">Wajib untuk role siswa (FK: students.user_id). Harus kosong untuk role guru.</span>
                </div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('users.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="save" class="icon-sm"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        (function(){
            function bind(roleId, fieldId){
                var sel = document.getElementById(roleId);
                var field = document.getElementById(fieldId);
                if (! sel || ! field) return;
                function sync(){
                    var isSiswa = sel.value === 'siswa';
                    field.style.display = isSiswa ? 'block' : 'none';
                    field.querySelector('select').required = isSiswa;
                    if (! isSiswa) field.querySelector('select').value = '';
                }
                sel.addEventListener('change', sync);
                sync();
            }
            document.addEventListener('DOMContentLoaded', function(){
                bind('roleSelectCreate','studentFieldCreate');
                bind('roleSelectEdit','studentFieldEdit');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        })();
    </script>
</x-app-layout>
