<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Pengaturan Sistem</h1>
                <p class="text-muted text-sm">Identitas sekolah, kop sertifikat, dan tanda tangan digital</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card card-form">
            @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
            <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')

                <h2 style="font-size:1rem;margin-bottom:var(--sp-md)">Aplikasi</h2>
                <div class="field"><label class="field-label">Nama Website / Aplikasi <span class="text-danger">*</span></label>
                    <input type="text" name="app_name" class="field-input" required maxlength="80" value="{{ old('app_name', $values['app_name']) }}">
                    <span class="help-text">Dipakai di tab browser dan judul aplikasi</span>
                </div>

                <h2 style="font-size:1rem;margin:var(--sp-lg) 0 var(--sp-md)">Identitas Sekolah</h2>
                <div class="field"><label class="field-label">Nama Sekolah <span class="text-danger">*</span></label>
                    <input type="text" name="school_name" class="field-input" required maxlength="120" value="{{ old('school_name', $values['school_name']) }}">
                </div>
                <div class="field"><label class="field-label">Alamat / Jalan</label>
                    <input type="text" name="school_address" class="field-input" maxlength="255" value="{{ old('school_address', $values['school_address']) }}" placeholder="Diisi manual oleh sekolah">
                    <span class="help-text">Alamat bersifat pribadi — boleh dikosongkan</span>
                </div>
                <div class="field"><label class="field-label">Telepon</label>
                    <input type="text" name="school_phone" class="field-input" maxlength="40" value="{{ old('school_phone', $values['school_phone']) }}" placeholder="Contoh: (0274) 512345">
                </div>

                <h2 style="font-size:1rem;margin:var(--sp-lg) 0 var(--sp-md)">Kop Sertifikat</h2>
                <div class="field"><label class="field-label">Baris Pemerintah <span class="text-danger">*</span></label>
                    <input type="text" name="kop_pemerintah" class="field-input" required maxlength="120" value="{{ old('kop_pemerintah', $values['kop_pemerintah']) }}" placeholder="Contoh: PEMERINTAH KABUPATEN BANTUL">
                </div>
                <div class="field"><label class="field-label">Baris Dinas <span class="text-danger">*</span></label>
                    <input type="text" name="kop_dinas" class="field-input" required maxlength="120" value="{{ old('kop_dinas', $values['kop_dinas']) }}" placeholder="Contoh: DINAS PENDIDIKAN DAN KEBUDAYAAN">
                </div>
                <div class="field"><label class="field-label">Footer Sertifikat <span class="text-danger">*</span></label>
                    <input type="text" name="certificate_footer" class="field-input" required maxlength="500" value="{{ old('certificate_footer', $values['certificate_footer']) }}">
                    <span class="help-text">Baris kecil paling bawah sertifikat</span>
                </div>

                <h2 style="font-size:1rem;margin:var(--sp-lg) 0 var(--sp-md)">Tanda Tangan &amp; Logo</h2>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:var(--sp-lg)">
                    <div>
                        <div class="field"><label class="field-label">Logo Sekolah</label>
                            <input type="file" name="logo" class="field-input" accept="image/*">
                            @if($values['logo_path'])
                                <div style="display:flex;align-items:center;gap:8px;margin-top:6px">
                                    <img src="{{ asset('storage/'.$values['logo_path']) }}" alt="Logo" style="width:40px;height:40px;object-fit:contain">
                                    <label class="help-text" style="display:inline-flex;gap:6px;cursor:pointer">
                                        <input type="checkbox" name="remove_logo" value="1"> Hapus logo
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="field"><label class="field-label">Tanda Tangan Kepala Sekolah</label>
                            <input type="file" name="ttd_kepala" class="field-input" accept="image/*">
                            @if($values['ttd_kepala_path'])
                                <div style="display:flex;align-items:center;gap:8px;margin-top:6px">
                                    <img src="{{ asset('storage/'.$values['ttd_kepala_path']) }}" alt="TTD Kepala Sekolah" style="max-height:56px">
                                    <label class="help-text" style="display:inline-flex;gap:6px;cursor:pointer">
                                        <input type="checkbox" name="remove_ttd_kepala" value="1"> Hapus TTD
                                    </label>
                                </div>
                            @endif
                            <span class="help-text">PNG transparan disarankan</span>
                        </div>
                        <div class="field"><label class="field-label">Nama Kepala Sekolah <span class="help-text" style="font-weight:normal">— identitas, diatur manual</span></label>
                            <input type="text" name="signer_kepala_nama" class="field-input" maxlength="120" value="{{ old('signer_kepala_nama', $values['signer_kepala_nama']) }}" placeholder="Nama pada tanda tangan">
                        </div>
                        <div class="field"><label class="field-label">NIP Kepala Sekolah <span class="help-text" style="font-weight:normal">— Nomor Induk Pegawai, boleh dikosongkan</span></label>
                            <input type="text" name="signer_kepala_nip" class="field-input" maxlength="40" value="{{ old('signer_kepala_nip', $values['signer_kepala_nip']) }}" placeholder="Contoh: 197012312000121001">
                        </div>
                    </div>
                    <div>
                        <div class="field"><label class="field-label">Tanda Tangan Kesiswaan</label>
                            <input type="file" name="ttd_kesiswaan" class="field-input" accept="image/*">
                            @if($values['ttd_kesiswaan_path'])
                                <div style="display:flex;align-items:center;gap:8px;margin-top:6px">
                                    <img src="{{ asset('storage/'.$values['ttd_kesiswaan_path']) }}" alt="TTD Kesiswaan" style="max-height:56px">
                                    <label class="help-text" style="display:inline-flex;gap:6px;cursor:pointer">
                                        <input type="checkbox" name="remove_ttd_kesiswaan" value="1"> Hapus TTD
                                    </label>
                                </div>
                            @endif
                            <span class="help-text">PNG transparan disarankan</span>
                        </div>
                        <div class="field"><label class="field-label">Nama Petugas Kesiswaan <span class="help-text" style="font-weight:normal">— identitas, diatur manual</span></label>
                            <input type="text" name="signer_kesiswaan_nama" class="field-input" maxlength="120" value="{{ old('signer_kesiswaan_nama', $values['signer_kesiswaan_nama']) }}" placeholder="Nama pada tanda tangan">
                        </div>
                        <div class="field"><label class="field-label">NIP Kesiswaan <span class="help-text" style="font-weight:normal">— Nomor Induk Pegawai, boleh dikosongkan</span></label>
                            <input type="text" name="signer_kesiswaan_nip" class="field-input" maxlength="40" value="{{ old('signer_kesiswaan_nip', $values['signer_kesiswaan_nip']) }}" placeholder="Contoh: 197012312000121002">
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="save" class="icon-sm"></i> Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
