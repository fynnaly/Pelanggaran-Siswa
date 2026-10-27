<x-app-layout>
    <x-slot name="header">
        <div class="page-header" x-data="{ isEditing: false }" @toggle-settings-edit.window="isEditing = $event.detail; if (!isEditing) { document.getElementById('settingsForm')?.reset(); }">
            <div>
                <h1>Pengaturan Sistem</h1>
                <p class="text-muted text-sm">Identitas sekolah, kop sertifikat, dan tanda tangan digital</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <button type="button" @click="if (isEditing) { document.getElementById('settingsForm')?.reset(); } $dispatch('toggle-settings-edit', !isEditing)" class="btn btn-primary btn-sm" :class="{ 'btn-secondary': isEditing, 'btn-primary': !isEditing }">
                    <span x-show="!isEditing" style="display:inline-flex;align-items:center;gap:6px">
                        <i data-lucide="edit-3" class="icon-sm"></i> Ubah Pengaturan
                    </span>
                    <span x-show="isEditing" x-cloak style="display:inline-flex;align-items:center;gap:6px">
                        <i data-lucide="x" class="icon-sm"></i> Batal
                    </span>
                </button>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="main-wrap" x-data="{ isEditing: false }" @toggle-settings-edit.window="isEditing = $event.detail; if (!isEditing) { document.getElementById('settingsForm')?.reset(); }">
        <div class="card card-form" style="max-width:840px">
            @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif

            <div x-show="!isEditing" style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-radius:var(--r-sm);background:var(--surface);border:1px solid var(--border);margin-bottom:var(--sp-md)">
                <div style="display:flex;align-items:center;gap:8px">
                    <span class="badge badge-neutral" style="font-size:0.75rem;font-weight:600;letter-spacing:0.05em;text-transform:uppercase">Read-only</span>
                    <span class="text-sm text-muted">Pengaturan dikunci. Aktifkan mode ubah untuk menyunting data.</span>
                </div>
                <button type="button" @click="$dispatch('toggle-settings-edit', true)" class="btn btn-primary btn-sm" style="padding:4px 10px;font-size:0.75rem">
                    Ubah
                </button>
            </div>

            <form id="settingsForm" action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" @submit.prevent="if (confirm('Anda yakin akan menyimpan? Pengaturan sebelumnya akan diubah.')) $el.submit()">
                @csrf @method('PUT')

                <h2 style="font-size:1rem;margin-bottom:var(--sp-md)">Identitas Sekolah &amp; Aplikasi</h2>
                <div class="settings-grid-header" style="display:grid;grid-template-columns:1fr 220px;gap:var(--sp-lg);align-items:start">
                    <div>
                        <div class="field"><label class="field-label">Nama Website / Aplikasi <span class="text-danger">*</span></label>
                            <input type="text" name="app_name" class="field-input" required maxlength="80" value="{{ old('app_name', $values['app_name']) }}" :readonly="!isEditing" :style="!isEditing ? 'background:var(--surface);cursor:default' : ''">
                            <span class="help-text">Nama yang tampil di tab peramban dan judul antarmuka</span>
                        </div>
                        <div class="field"><label class="field-label">Nama Sekolah <span class="text-danger">*</span></label>
                            <input type="text" name="school_name" class="field-input" required maxlength="120" value="{{ old('school_name', $values['school_name']) }}" :readonly="!isEditing" :style="!isEditing ? 'background:var(--surface);cursor:default' : ''">
                        </div>
                        <div class="field"><label class="field-label">Alamat Instansi</label>
                            <input type="text" name="school_address" class="field-input" maxlength="255" value="{{ old('school_address', $values['school_address']) }}" placeholder="Contoh: Jl. Raya No. 123" :readonly="!isEditing" :style="!isEditing ? 'background:var(--surface);cursor:default' : ''">
                            <span class="help-text">Alamat resmi instansi sekolah (opsional)</span>
                        </div>
                        <div class="field"><label class="field-label">Nomor Telepon</label>
                            <input type="text" name="school_phone" class="field-input" maxlength="40" value="{{ old('school_phone', $values['school_phone']) }}" placeholder="Contoh: 08123456789" :readonly="!isEditing" :style="!isEditing ? 'background:var(--surface);cursor:default' : ''">
                        </div>
                    </div>

                    <div>
                        <div class="field">
                            <label class="field-label">Logo Sekolah</label>
                            <div style="border:1px dashed var(--border);border-radius:var(--r-md);padding:var(--sp-md);text-align:center;background:var(--surface)">
                                @if($values['logo_path'])
                                    <div style="margin-bottom:var(--sp-sm)">
                                        <img src="{{ asset('storage/'.$values['logo_path']) }}" alt="Logo" style="width:80px;height:80px;object-fit:contain;margin:0 auto">
                                    </div>
                                    <label x-show="isEditing" x-cloak class="help-text" style="display:inline-flex;gap:6px;cursor:pointer;justify-content:center">
                                        <input type="checkbox" name="remove_logo" value="1"> Hapus logo
                                    </label>
                                @else
                                    <div style="height:80px;display:flex;align-items:center;justify-content:center;color:var(--on-surface-muted);font-size:.8125rem">
                                        Belum ada logo
                                    </div>
                                @endif
                                <div x-show="isEditing" x-cloak style="margin-top:var(--sp-sm)">
                                    <input type="file" name="logo" class="field-input" accept="image/*" style="font-size:.75rem">
                                    <span class="help-text">Format PNG/JPG</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h2 style="font-size:1rem;margin:var(--sp-xl) 0 var(--sp-md)">Kop Dokumen &amp; Sertifikat</h2>
                <div class="field"><label class="field-label">Baris Pemerintah <span class="text-danger">*</span></label>
                    <input type="text" name="kop_pemerintah" class="field-input" required maxlength="120" value="{{ old('kop_pemerintah', $values['kop_pemerintah']) }}" placeholder="Contoh: PEMERINTAH DAERAH PROVINSI JAWA BARAT" :readonly="!isEditing" :style="!isEditing ? 'background:var(--surface);cursor:default' : ''">
                </div>
                <div class="field"><label class="field-label">Baris Dinas <span class="text-danger">*</span></label>
                    <input type="text" name="kop_dinas" class="field-input" required maxlength="120" value="{{ old('kop_dinas', $values['kop_dinas']) }}" placeholder="Contoh: DINAS PENDIDIKAN" :readonly="!isEditing" :style="!isEditing ? 'background:var(--surface);cursor:default' : ''">
                </div>
                <div class="field"><label class="field-label">Footer Sertifikat <span class="text-danger">*</span></label>
                    <input type="text" name="certificate_footer" class="field-input" required maxlength="500" value="{{ old('certificate_footer', $values['certificate_footer']) }}" :readonly="!isEditing" :style="!isEditing ? 'background:var(--surface);cursor:default' : ''">
                    <span class="help-text">Catatan kaki legalitas pada baris bawah sertifikat</span>
                </div>

                <h2 style="font-size:1rem;margin:var(--sp-xl) 0 var(--sp-md)">Pejabat Penandatangan Dokumen</h2>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:var(--sp-lg)">
                    <!-- Kolom 1: Kepala Sekolah -->
                    <div style="border:1px solid var(--border);border-radius:var(--r-md);padding:var(--sp-md);background:var(--surface)">
                        <h3 style="font-size:.9375rem;font-weight:600;margin:0 0 var(--sp-sm) 0">Kepala Sekolah</h3>
                        <div class="field">
                            <label class="field-label">Tanda Tangan Digital</label>
                            @if($values['ttd_kepala_path'])
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;background:var(--neutral);padding:6px;border-radius:var(--r-sm);border:1px solid var(--border)">
                                    <img src="{{ asset('storage/'.$values['ttd_kepala_path']) }}" alt="TTD Kepala Sekolah" style="max-height:48px;object-fit:contain">
                                    <label x-show="isEditing" x-cloak class="help-text" style="display:inline-flex;gap:4px;cursor:pointer;color:var(--danger)">
                                        <input type="checkbox" name="remove_ttd_kepala" value="1"> Hapus TTD
                                    </label>
                                </div>
                            @else
                                <div x-show="!isEditing" class="text-muted text-sm" style="margin-bottom:6px">Belum ada TTD.</div>
                            @endif
                            <div x-show="isEditing" x-cloak>
                                <input type="file" name="ttd_kepala" class="field-input" accept="image/*" style="font-size:.75rem">
                                <span class="help-text">Disarankan PNG transparan</span>
                            </div>
                        </div>
                        <div class="field"><label class="field-label">Nama Kepala Sekolah <span class="help-text" style="font-weight:normal">(Diatur manual)</span></label>
                            <input type="text" name="signer_kepala_nama" class="field-input" maxlength="120" value="{{ old('signer_kepala_nama', $values['signer_kepala_nama']) }}" placeholder="Nama lengkap beserta gelar" :readonly="!isEditing" :style="!isEditing ? 'background:var(--neutral);cursor:default' : ''">
                        </div>
                        <div class="field"><label class="field-label">NIP Kepala Sekolah <span class="help-text" style="font-weight:normal">(Nomor Induk Pegawai, opsional)</span></label>
                            <input type="text" name="signer_kepala_nip" class="field-input" maxlength="40" value="{{ old('signer_kepala_nip', $values['signer_kepala_nip']) }}" placeholder="Contoh: 197012312000121001" :readonly="!isEditing" :style="!isEditing ? 'background:var(--neutral);cursor:default' : ''">
                        </div>
                    </div>

                    <!-- Kolom 2: Tim Kesiswaan -->
                    <div style="border:1px solid var(--border);border-radius:var(--r-md);padding:var(--sp-md);background:var(--surface)">
                        <h3 style="font-size:.9375rem;font-weight:600;margin:0 0 var(--sp-sm) 0">Tim Kesiswaan</h3>
                        <div class="field">
                            <label class="field-label">Tanda Tangan Digital</label>
                            @if($values['ttd_kesiswaan_path'])
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;background:var(--neutral);padding:6px;border-radius:var(--r-sm);border:1px solid var(--border)">
                                    <img src="{{ asset('storage/'.$values['ttd_kesiswaan_path']) }}" alt="TTD Kesiswaan" style="max-height:48px;object-fit:contain">
                                    <label x-show="isEditing" x-cloak class="help-text" style="display:inline-flex;gap:4px;cursor:pointer;color:var(--danger)">
                                        <input type="checkbox" name="remove_ttd_kesiswaan" value="1"> Hapus TTD
                                    </label>
                                </div>
                            @else
                                <div x-show="!isEditing" class="text-muted text-sm" style="margin-bottom:6px">Belum ada TTD.</div>
                            @endif
                            <div x-show="isEditing" x-cloak>
                                <input type="file" name="ttd_kesiswaan" class="field-input" accept="image/*" style="font-size:.75rem">
                                <span class="help-text">Disarankan PNG transparan</span>
                            </div>
                        </div>
                        <div class="field"><label class="field-label">Nama Petugas Kesiswaan <span class="help-text" style="font-weight:normal">(Diatur manual)</span></label>
                            <input type="text" name="signer_kesiswaan_nama" class="field-input" maxlength="120" value="{{ old('signer_kesiswaan_nama', $values['signer_kesiswaan_nama']) }}" placeholder="Nama lengkap beserta gelar" :readonly="!isEditing" :style="!isEditing ? 'background:var(--neutral);cursor:default' : ''">
                        </div>
                        <div class="field"><label class="field-label">NIP Kesiswaan <span class="help-text" style="font-weight:normal">(Nomor Induk Pegawai, opsional)</span></label>
                            <input type="text" name="signer_kesiswaan_nip" class="field-input" maxlength="40" value="{{ old('signer_kesiswaan_nip', $values['signer_kesiswaan_nip']) }}" placeholder="Contoh: 197012312000121002" :readonly="!isEditing" :style="!isEditing ? 'background:var(--neutral);cursor:default' : ''">
                        </div>
                    </div>
                </div>

                <div x-show="isEditing" x-cloak style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-xl)">
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="save" class="icon-sm"></i> Simpan Pengaturan</button>
                    <button type="button" @click="document.getElementById('settingsForm')?.reset(); $dispatch('toggle-settings-edit', false)" class="btn btn-secondary" style="justify-content:center"><i data-lucide="x" class="icon-sm"></i> Batal</button>
                </div>
            </form>
        </div>
    </div>
    <style>
        @media (max-width: 680px) {
            .settings-grid-header { grid-template-columns: 1fr !important; }
        }
    </style>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
