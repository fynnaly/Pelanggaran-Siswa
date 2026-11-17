<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Tambah Data Guru</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('teachers.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card card-form">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
            <form action="{{ route('teachers.store') }}" method="POST">
                @csrf
                <div class="field">
                    <label class="field-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" class="field-input" required>
                </div>
                <div class="field">
                    <label class="field-label">Jenis Kelamin</label>
                    <select name="gender" class="field-input">
                        <option value="">Belum diisi</option>
                        <option value="L" {{ old('gender') === 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ old('gender') === 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>
                <div class="field">
                    <label class="field-label">NIP</label>
                    <input type="text" name="nip" value="{{ old('nip') }}" class="field-input" placeholder="Nomor Induk Pegawai">
                    <span class="help-text">Opsional, harus unik antar guru.</span>
                </div>
                <div class="field">
                    <label class="field-label">Mata Pelajaran</label>
                    <input type="text" name="mata_pelajaran" value="{{ old('mata_pelajaran') }}" class="field-input" placeholder="Contoh: Matematika">
                </div>
                <div class="field">
                    <label class="field-label">Jabatan</label>
                    <input type="text" name="jabatan" value="{{ old('jabatan') }}" class="field-input" placeholder="Contoh: Kepala Sekolah / Wks. Kesiswaan">
                    <span class="help-text">Jabatan struktural, bukan mata pelajaran.</span>
                </div>
                <div class="field">
                    <label class="field-label">Telepon</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="field-input" placeholder="08xxxxxxxxxx">
                </div>
                <div class="field">
                    <label class="field-label">Akun Pengguna</label>
                    <select name="user_id" class="field-input">
                        <option value="">Tidak ditautkan</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('user_id')==$account->id?'selected':'' }}>
                                {{ $account->name }} ({{ $account->email }})
                            </option>
                        @endforeach
                    </select>
                    <span class="help-text">Opsional. Hanya akun ber-role guru/wali kelas yang belum tertaut. Foto profil mengikuti akun pengguna.</span>
                </div>
                <div class="field">
                    <label class="field-label">Status</label>
                    <select name="status" class="field-input">
                        <option value="active" {{ old('status', 'active')==='active'?'selected':'' }}>Aktif</option>
                        <option value="inactive" {{ old('status')==='inactive'?'selected':'' }}>Nonaktif</option>
                    </select>
                </div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('teachers.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="save" class="icon-sm"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
