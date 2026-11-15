<x-app-layout>
    <x-slot name="header">

        <div class="page-header">
            <div><h1>Tambah Kategori</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('violation-categories.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap" x-data="{ showToleranceTip: false }">
        <div class="card card-form">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
            <form action="{{ route('violation-categories.store') }}" method="POST">
                @csrf
                <div class="field"><label class="field-label">Kode <span class="text-danger">*</span></label><input type="text" name="code" value="{{ old('code') }}" class="field-input" required placeholder="Contoh: Pel-001"></div>
                <div class="field"><label class="field-label">Nama <span class="text-danger">*</span></label><input type="text" name="name" value="{{ old('name') }}" class="field-input" required placeholder="Contoh: Terlambat Masuk"></div>
                <div class="field"><label class="field-label">Induk Kategori</label>
                    <select name="parent_id" class="field-input">
                        <option value="">Tidak ada — jadikan kategori induk</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_id')==$parent->id?'selected':'' }}>{{ $parent->code }} — {{ $parent->name }}</option>
                        @endforeach
                    </select>
                    <span class="help-text">Kosongkan bila ini kategori besar (misal Seragam). Isi bila ini sub-kategori dari kategori di atas.</span>
                </div>
                <div class="field"><label class="field-label">Tingkat <span class="text-danger">*</span></label>
                    <select name="severity" class="field-input" required>
                        <option value="">Pilih tingkat...</option>
                        <option value="ringan" {{ old('severity')==='ringan'?'selected':'' }}>Ringan</option>
                        <option value="sedang" {{ old('severity')==='sedang'?'selected':'' }}>Sedang</option>
                        <option value="berat" {{ old('severity')==='berat'?'selected':'' }}>Berat</option>
                        <option value="sangat_berat" {{ old('severity')==='sangat_berat'?'selected':'' }}>Sangat Berat (Zero Tolerance)</option>
                    </select>
                    <span class="help-text">Skala acuan sekolah: 8 (Ringan), 16 (Sedang), 32 (Berat), 64 (Sangat Berat / Zero Tolerance).</span>
                </div>
                <div class="field"><label class="field-label">Status <span class="text-danger">*</span></label>
                    <input type="text" class="field-input cursor-not-allowed bg-slate-100 dark:bg-zinc-800 text-slate-500" value="Aktif" disabled style="background:var(--surface);cursor:not-allowed">
                    <input type="hidden" name="status" value="active">
                    <span class="help-text">Kategori baru otomatis berstatus aktif</span>
                </div>
                <div class="field">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px">
                        <label class="field-label" style="margin:0">Batas Toleransi Poin <span class="text-danger">*</span></label>
                        <button type="button" @click="showToleranceTip = true; $nextTick(() => { if (window.lucide) lucide.createIcons(); })" class="btn btn-secondary" style="padding:2px 6px;border-radius:var(--r-full);line-height:1;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center" title="Bantuan aturan toleransi poin">
                            <i data-lucide="info" style="width:12px;height:12px"></i>
                        </button>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:var(--sp-sm)">
                        <div>
                            <input type="number" name="poin_ke_1" value="{{ old('poin_ke_1') }}" class="field-input" required min="0" max="1000" placeholder="Ke-1">
                            <span class="help-text">Pelanggaran ke-1</span>
                        </div>
                        <div>
                            <input type="number" name="poin_ke_2" value="{{ old('poin_ke_2') }}" class="field-input" min="0" max="1000" placeholder="Ke-2">
                            <span class="help-text">Pelanggaran ke-2</span>
                        </div>
                        <div>
                            <input type="number" name="poin_ke_3" value="{{ old('poin_ke_3') }}" class="field-input" min="0" max="1000" placeholder="Ke-3">
                            <span class="help-text">Pelanggaran ke-3 (batas)</span>
                        </div>
                    </div>
                    <span class="help-text">Poin debit per insiden berulang (Ke-1, Ke-2, batas Ke-3). Klik ikon (i) di atas untuk panduan lengkap.</span>
                </div>

                <!-- Modal Panduan Toleransi Poin -->
                <template x-teleport="body">
                    <div x-show="showToleranceTip" x-cloak class="modal-overlay" style="display:none">
                        <div style="position:fixed;inset:0;z-index:1" @click="showToleranceTip = false"></div>
                        <div x-transition style="background:var(--neutral);border:1px solid var(--border);border-radius:var(--r-lg);padding:var(--sp-lg);max-width:500px;width:100%;position:relative;z-index:2;box-shadow:var(--sh-md)">
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:var(--sp-md)">
                                <div style="width:36px;height:36px;border-radius:var(--r-full);background:var(--bs);color:var(--bt);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <i data-lucide="info" class="icon-sm"></i>
                                </div>
                                <div>
                                    <h3 style="font-size:1.125rem;font-weight:700;margin:0">Panduan Toleransi Poin</h3>
                                    <span class="text-xs text-muted">Pedoman Pengulangan Pelanggaran Siswa</span>
                                </div>
                            </div>

                            <div style="font-size:.875rem;line-height:1.6;color:var(--on-surface)">
                                <p style="margin-top:0">Sekolah menerapkan sistem pembinaan bertingkat (eskalasi penalti). Semakin sering siswa mengulangi pelanggaran yang sama, poin pengurangan meningkat:</p>
                                <ul style="padding-left:20px;margin:10px 0">
                                    <li><strong>Pelanggaran Ke-1</strong>: Poin penalti saat pertama kali insiden terjadi (contoh: 8 poin).</li>
                                    <li><strong>Pelanggaran Ke-2</strong>: Poin penalti jika siswa mengulangi lagi pada tahun berjalan (contoh: 16 poin).</li>
                                    <li><strong>Pelanggaran Ke-3</strong>: Batas toleransi akhir (contoh: 32 poin). Kejadian ke-4 dan seterusnya tetap dikenakan poin ke-3 ini.</li>
                                </ul>
                                <div class="alert alert-info" style="font-size:.8125rem;margin:12px 0 0">
                                    <strong>Catatan:</strong> Jika pelanggaran tidak memiliki sistem pengulangan (flat penalty), cukup isi <strong>Ke-1</strong> dan kosongkan kolom Ke-2 serta Ke-3.
                                </div>
                            </div>

                            <div style="display:flex;justify-content:flex-end;margin-top:var(--sp-md)">
                                <button type="button" class="btn btn-primary btn-sm" @click="showToleranceTip = false">Mengerti</button>
                            </div>
                        </div>
                    </div>
                </template>
                <div class="field">
                    <label class="field-label">Sanksi (otomatis dari daftar prestasi)</label>
                    <input type="text" class="field-input" value="Otomatis — daftar prestasi wajib (redemption rules)" readonly style="background:var(--surface);color:var(--on-surface-muted)">
                    <span class="help-text">Sanksi diturunkan dari daftar prestasi pemulihan. Tanpa prestasi divalidasi, poin siswa tertahan (tidak naik). Setelah kategori disimpan dan dipasangkan redemption rules, daftar tampil di halaman edit & detail kasus.</span>
                </div>
                <div class="field"><label class="field-label">Deskripsi</label><textarea name="description" class="field-input" rows="3" placeholder="Opsional...">{{ old('description') }}</textarea></div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('violation-categories.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="save" class="icon-sm"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
