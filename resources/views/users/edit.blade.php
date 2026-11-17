<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Edit: {{ $user->name }}</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                @if(! $user->is(auth()->user()))
                    <form method="POST" action="{{ route('users.password.reset', $user) }}"
                          onsubmit="return confirm('Reset password akun ini? Password lama akan langsung tidak berlaku.')">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="key-round" class="icon-sm"></i> Reset Password</button>
                    </form>
                @endif
                <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
        <div class="card card-form">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
@php $currentRole = old('role', $user->roles->first()?->name) @endphp
            <form action="{{ route('users.update', $user) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div style="display:flex;align-items:center;gap:var(--sp-md);margin-bottom:var(--sp-lg)">
                    <img src="{{ $user->avatar_url }}" alt="Foto {{ $user->name }}"
                         style="width:72px;height:72px;border-radius:999px;object-fit:cover;flex-shrink:0;border:1px solid var(--border);background:var(--surface)">
                    <div style="flex:1;min-width:0">
                        <label class="field-label" for="profileAvatar">Foto Profil</label>
                        <input id="profileAvatar" type="file" name="avatar" accept="image/png,image/jpeg,image/webp" class="field-input" style="padding:6px 10px">
                        <span class="help-text">PNG/JPG/WEBP, maks. 15MB. Foto juga dipakai pada data siswa/guru yang tertaut.</span>
                        @if($user->avatar_path)
                            <label style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;font-size:.8125rem;cursor:pointer">
                                <input type="checkbox" name="remove_avatar" value="1" {{ old('remove_avatar') ? 'checked' : '' }}> Hapus foto saat ini
                            </label>
                        @endif
                    </div>
                </div>
                <div class="field"><label class="field-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="field-input" required>
                </div>
                <div class="field"><label class="field-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="field-input" required>
                </div>
                <div class="field"><label class="field-label">NIP</label>
                    <input type="text" name="nip" value="{{ old('nip', $user->nip) }}" maxlength="40" class="field-input" placeholder="Opsional">
                    <span class="help-text">Nomor induk pegawai untuk akun guru/staf. Boleh dikosongkan.</span>
                </div>
                <div class="field"><label class="field-label">Password Baru</label>
                    <input type="password" name="password" class="field-input" minlength="8">
                    <span class="help-text">Kosongkan jika tidak ingin mengubah password.</span>
                </div>
                <div class="field"><label class="field-label">Role / Jabatan <span class="text-danger">*</span></label>
                    <select name="role" id="roleSelectEdit" class="field-input" required>
                        <option value="">Pilih role...</option>
                        @foreach($roles as $r)
                            <option value="{{ $r }}" {{ $currentRole===$r?'selected':'' }}>{{ $r }}</option>
                        @endforeach
                    </select>
                    <span class="help-text">Guru: guru, bk, kesiswaan, kepala_sekolah, admin. Siswa: siswa.</span>
                </div>
                <div class="field" id="studentFieldEdit"><label class="field-label">Identitas Siswa</label>
                    <select name="student_id" class="field-input">
                        <option value="">Tidak terkait</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}" {{ old('student_id', $user->student?->id)==$s->id?'selected':'' }}>{{ $s->full_name }} ({{ $s->nis }} / {{ $s->nisn }})</option>
                        @endforeach
                    </select>
                    <span class="help-text">Wajib untuk role siswa (FK: students.user_id). Harus kosong untuk role guru.</span>
                </div>
                <div class="field" id="teacherFieldEdit"><label class="field-label">Identitas Guru</label>
                    <select name="teacher_id" class="field-input">
                        <option value="">Tidak terkait</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" {{ old('teacher_id', $user->teacher?->id)==$t->id?'selected':'' }}>{{ $t->full_name }} ({{ $t->nip ?: 'tanpa NIP' }}{{ $t->mata_pelajaran ? ' · '.$t->mata_pelajaran : '' }})</option>
                        @endforeach
                    </select>
                    <span class="help-text">Opsional untuk role guru / wali kelas. Sumber data: menu Data Guru.</span>
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
            function bind(roleId, studentFieldId, teacherFieldId){
                var sel = document.getElementById(roleId);
                var studentField = document.getElementById(studentFieldId);
                var teacherField = document.getElementById(teacherFieldId);
                if (! sel) return;

                function toggle(field, visible){
                    if (! field) return;
                    field.style.display = visible ? 'block' : 'none';
                    var input = field.querySelector('select');
                    if (! input) return;
                    input.disabled = ! visible;
                    if (! visible) input.value = '';
                }

                function sync(){
                    var role = sel.value;
                    var isSiswa = role === 'siswa';
                    var isTeacher = role === 'guru' || role === 'wali_kelas';
                    toggle(studentField, isSiswa);
                    toggle(teacherField, isTeacher);
                }

                sel.addEventListener('change', sync);
                sync();
            }
            document.addEventListener('DOMContentLoaded', function(){
                bind('roleSelectCreate','studentFieldCreate','teacherFieldCreate');
                bind('roleSelectEdit','studentFieldEdit','teacherFieldEdit');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        })();
    </script>
</x-app-layout>
