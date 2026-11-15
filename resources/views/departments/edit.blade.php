<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Edit Jurusan {{ $department->code }}</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('jurusan.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card card-form">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
            <form action="{{ route('jurusan.update', $department) }}" method="POST">
                @csrf @method('PUT')
                <div class="field"><label class="field-label">Kode Jurusan <span class="text-danger">*</span></label><input type="text" name="code" value="{{ old('code', $department->code) }}" maxlength="20" class="field-input" required><span class="help-text">Maks. 20 karakter, huruf/angka/tanda hubung, unik.</span></div>
                <div class="field"><label class="field-label">Nama Jurusan <span class="text-danger">*</span></label><input type="text" name="name" value="{{ old('name', $department->name) }}" maxlength="100" class="field-input" required></div>
                <div class="field"><label class="field-label">Deskripsi</label><textarea name="description" rows="4" maxlength="1000" class="field-input">{{ old('description', $department->description) }}</textarea></div>
                <div class="field"><label class="field-label">Status</label><select name="status" class="field-input"><option value="active" {{ old('status', $department->status) === 'active' ? 'selected' : '' }}>Aktif</option><option value="inactive" {{ old('status', $department->status) === 'inactive' ? 'selected' : '' }}>Nonaktif</option></select></div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('jurusan.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="save" class="icon-sm"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
