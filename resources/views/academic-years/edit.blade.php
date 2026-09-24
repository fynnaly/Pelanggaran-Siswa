<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Edit Tahun Ajaran</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('tahun-ajaran.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card card-form">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
            <form action="{{ route('tahun-ajaran.update', $academicYear) }}" method="POST" onsubmit="return !this.is_active.checked || {{ $academicYear->is_active ? 'true' : "confirm('Mengaktifkan tahun ajaran ini akan menjalankan kenaikan kelas otomatis (X→XI, XI→XII, XII→lulus) dan generate saldo awal. Lanjutkan?')" }}">
                @csrf @method('PUT')
                <div class="field"><label class="field-label">Nama Tahun Ajaran <span class="text-danger">*</span></label><input type="text" name="name" value="{{ old('name', $academicYear->name) }}" class="field-input" required></div>
                <div class="field"><label class="field-label">Tanggal Mulai <span class="text-danger">*</span></label><input type="date" name="start_date" value="{{ old('start_date', $academicYear->start_date?->format('Y-m-d')) }}" class="field-input" required></div>
                <div class="field"><div class="checkbox-row"><input type="checkbox" name="is_active" value="1" class="checkbox-input" {{ old('is_active', $academicYear->is_active)?'checked':'' }}><span class="text-sm">Jadikan tahun ajaran ini aktif</span></div><span class="help-text">Jika diaktifkan, sistem akan menjalankan kenaikan kelas (X→XI, XI→XII, XII→lulus) serta generate saldo awal.</span></div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('tahun-ajaran.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="save" class="icon-sm"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
