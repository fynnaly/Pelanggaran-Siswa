<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Kategori Pelanggaran / Edit</div>
        <div class="page-header"><h1>Edit: {{ $violationCategory->code }}</h1></div>
    </x-slot>
    <div class="main-wrap">
        <div class="card" style="max-width:600px">
            @if($errors->any())
                <div class="alert alert-error" style="margin-bottom:var(--sp-lg)">{{ $errors->first() }}</div>
            @endif
            <form action="{{ route('violation-categories.update', $violationCategory) }}" method="POST">
                @csrf @method('PUT')
                <div class="field">
                    <label class="field-label">Kode <span class="text-danger">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $violationCategory->code) }}" required maxlength="20" class="field-input">
                </div>
                <div class="field">
                    <label class="field-label">Nama <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $violationCategory->name) }}" required maxlength="150" class="field-input">
                </div>
                <div class="field">
                    <label class="field-label">Tingkat <span class="text-danger">*</span></label>
                    <select name="severity" required class="field-input">
                        <option value="ringan" {{ old('severity', $violationCategory->severity)==='ringan'?'selected':'' }}>Ringan</option>
                        <option value="sedang" {{ old('severity', $violationCategory->severity)==='sedang'?'selected':'' }}>Sedang</option>
                        <option value="berat" {{ old('severity', $violationCategory->severity)==='berat'?'selected':'' }}>Berat</option>
                    </select>
                </div>
                <div class="field">
                    <label class="field-label">Poin <span class="text-danger">*</span></label>
                    <input type="number" name="points" value="{{ old('points', $violationCategory->points) }}" required min="1" class="field-input">
                </div>
                <div class="field">
                    <label class="field-label">Status <span class="text-danger">*</span></label>
                    <select name="status" required class="field-input">
                        <option value="active" {{ old('status', $violationCategory->status)==='active'?'selected':'' }}>Active</option>
                        <option value="draft" {{ old('status', $violationCategory->status)==='draft'?'selected':'' }}>Draft</option>
                    </select>
                </div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('violation-categories.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>