<x-app-layout>
    <x-slot name="header">

        <div class="page-header">
            <div><h1>Tambah Kategori</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('violation-categories.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
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
                    </select>
                </div>
                <div class="field"><label class="field-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="field-input" required>
                        <option value="">Pilih status...</option>
                        <option value="draft" {{ old('status')==='draft'?'selected':'' }}>Draft</option>
                        <option value="active" {{ old('status')==='active'?'selected':'' }}>Aktif</option>
                    </select>
                </div>
                <div class="field">
                    <label class="field-label">Batas Toleransi Poin <span class="text-danger">*</span></label>
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
                    <span class="help-text">Tiga batas pengurangan poin saat pelanggaran diulang; pelanggaran ke-4 dan seterusnya tetap memakai poin ke-3. Kosongkan ke-2 dan ke-3 bila pelanggaran tidak berulang. Isi 0 atau kosongkan batas yang tidak dipakai — contoh: ke-3 = 0 → hanya 2 toleransi (ke-I + ke-II). Contoh Terlambat kurang dari 10 menit: 5 / 10 / 15.</span>
                </div>
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
