<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Tambah Jurusan</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('jurusan.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card card-form">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
            <form action="{{ route('jurusan.store') }}" method="POST">
                @csrf
                <div class="field"><label class="field-label">Kode Jurusan <span class="text-danger">*</span></label><input type="text" name="code" value="{{ old('code') }}" maxlength="20" class="field-input" placeholder="mis. RPL" required><span class="help-text">Maks. 20 karakter, huruf/angka/tanda hubung, unik.</span></div>
                <div class="field"><label class="field-label">Nama Jurusan <span class="text-danger">*</span></label><input type="text" name="name" value="{{ old('name') }}" maxlength="100" class="field-input" placeholder="mis. Rekayasa Perangkat Lunak" required></div>
                <div class="field"><label class="field-label">Deskripsi</label><textarea name="description" rows="4" maxlength="1000" class="field-input" placeholder="Konsentrasi keahlian...">{{ old('description') }}</textarea></div>
                <div class="field"><label class="field-label">Status</label><input type="text" class="field-input cursor-not-allowed" value="Aktif" disabled style="background:var(--surface);cursor:not-allowed"><input type="hidden" name="status" value="active"><span class="help-text">Jurusan baru otomatis aktif. Status bisa diubah lewat toggle aktif/nonaktif atau saat edit.</span></div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('jurusan.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="save" class="icon-sm"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
