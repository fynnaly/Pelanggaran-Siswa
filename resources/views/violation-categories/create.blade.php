<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Kategori / Tambah</div>
        <div class="page-header">
            <div><h1>Tambah Kategori</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('violation-categories.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card" style="max-width:600px">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
            <form action="{{ route('violation-categories.store') }}" method="POST">
                @csrf
                <div class="field"><label class="field-label">Kode <span class="text-danger">*</span></label><input type="text" name="code" value="{{ old('code') }}" class="field-input" required placeholder="Contoh: TERLAMBAT"></div>
                <div class="field"><label class="field-label">Nama <span class="text-danger">*</span></label><input type="text" name="name" value="{{ old('name') }}" class="field-input" required placeholder="Contoh: Terlambat Masuk"></div>
                <div class="field"><label class="field-label">Tingkat <span class="text-danger">*</span></label>
                    <select name="severity" class="field-input" required>
                        <option value="">Pilih tingkat...</option>
                        <option value="ringan" {{ old('severity')==='ringan'?'selected':'' }}>Ringan</option>
                        <option value="sedang" {{ old('severity')==='sedang'?'selected':'' }}>Sedang</option>
                        <option value="berat" {{ old('severity')==='berat'?'selected':'' }}>Berat</option>
                    </select>
                </div>
                <div class="field"><label class="field-label">Poin <span class="text-danger">*</span></label><input type="number" name="points" value="{{ old('points') }}" class="field-input" required min="0" placeholder="0"></div>
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