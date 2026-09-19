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
        <div class="card">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
            <form action="{{ route('students.store') }}" method="POST">
                @csrf
                <div class="field"><label class="field-label">NISN <span class="text-danger">*</span></label><input type="text" name="nisn" value="{{ old('nisn') }}" class="field-input" required maxlength="20" placeholder="Nomor Induk Siswa Nasional"></div>
                <div class="field"><label class="field-label">NIS <span class="text-danger">*</span></label><input type="text" name="nis" value="{{ old('nis') }}" class="field-input" required maxlength="20" placeholder="Nomor Induk Siswa"></div>
                <div class="field"><label class="field-label">Nama Lengkap <span class="text-danger">*</span></label><input type="text" name="full_name" value="{{ old('full_name') }}" class="field-input" required maxlength="255"></div>
                <div class="field"><label class="field-label">Kelas <span class="text-danger">*</span></label>
                    <select name="school_class_id" class="field-input" required>
                        <option value="">Pilih kelas...</option>
                        @foreach($classes as $id => $name)
                            <option value="{{ $id }}" {{ old('school_class_id')==$id?'selected':'' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="field-label">Status</label>
                    <select name="status" class="field-input">
                        <option value="active" {{ old('status')==='active'?'selected':'' }}>Aktif</option>
                        <option value="inactive" {{ old('status')==='inactive'?'selected':'' }}>Tidak Aktif</option>
                        <option value="graduated" {{ old('status')==='graduated'?'selected':'' }}>Lulus</option>
                        <option value="transferred" {{ old('status')==='transferred'?'selected':'' }}>Pindah</option>
                    </select>
                </div>
                <div class="field"><label class="field-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="field-input" placeholder="Opsional"></div>
                <div class="field"><label class="field-label">No. Telepon</label><input type="text" name="phone" value="{{ old('phone') }}" class="field-input" placeholder="Opsional"></div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('students.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="save" class="icon-sm"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>