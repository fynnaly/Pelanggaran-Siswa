<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Tahun Ajaran / Tambah</div>
        <div class="page-header">
            <h1>Tambah Tahun Ajaran</h1>
        </div>
    </x-slot>

    <div class="main-wrap">
        <div class="card" style="max-width:600px">
            @if($errors->any())
                <div class="alert alert-error" style="margin-bottom:var(--sp-lg)">{{ $errors->first() }}</div>
            @endif
            <form action="{{ route('academic-years.store') }}" method="POST">
                @csrf
                <div class="field">
                    <label class="field-label" for="name">Nama Tahun Ajaran <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" class="field-input" placeholder="Contoh: 2026/2027" required>
                    @error('name') <span class="text-danger" style="font-size:.8125rem">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label class="field-label" for="start_date">Tanggal Mulai <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" class="field-input" required>
                    @error('start_date') <span class="text-danger" style="font-size:.8125rem">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <div class="checkbox-row">
                        <input type="checkbox" name="is_active" value="1" class="checkbox-input" {{ old('is_active') ? 'checked' : '' }}>
                        <span class="text-sm">Jadikan tahun ajaran ini aktif</span>
                    </div>
                    <span class="help-text">Jika diaktifkan, tahun ajaran lain yang aktif akan otomatis dinonaktifkan.</span>
                </div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('academic-years.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
