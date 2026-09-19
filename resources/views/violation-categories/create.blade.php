<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Kategori Pelanggaran / Tambah</div>
        <div class="page-header"><h1>Tambah Kategori</h1></div>
    </x-slot>
    <div class="main-wrap">
        <div class="card" style="max-width:600px">
            @if($errors->any())
                <div class="alert alert-error" style="margin-bottom:var(--sp-lg)">{{ $errors->first() }}</div>
            @endif
            <form action="{{ route('violation-categories.store') }}" method="POST">
                @csrf
                <div class="field">
                    <label class="field-label">Kode <span class="text-danger">*</span></label>
                    <input type="text" name="code" required maxlength="20" class="field-input" placeholder="Contoh: BK01">
                </div>
                <div class="field">
                    <label class="field-label">Nama <span class="text-danger">*</span></label>
                    <input type="text" name="name" required maxlength="150" class="field-input" placeholder="Contoh: Terlambat Masuk">
                </div>
                <div class="field">
                    <label class="field-label">Tingkat <span class="text-danger">*</span></label>
                    <select name="severity" required class="field-input">
                        <option value="ringan">Ringan</option>
                        <option value="sedang">Sedang</option>
                        <option value="berat">Berat</option>
                    </select>
                </div>
                <div class="field">
                    <label class="field-label">Poin <span class="text-danger">*</span></label>
                    <input type="number" name="points" required min="1" class="field-input" value="10">
                </div>
                <div class="field">
                    <label class="field-label">Status <span class="text-danger">*</span></label>
                    <select name="status" required class="field-input">
                        <option value="active">Active</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('violation-categories.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>