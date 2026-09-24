<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Tambah Kelas</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('classes.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card card-form">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
            <form action="{{ route('classes.store') }}" method="POST">
                @csrf
                <div class="field"><label class="field-label">Nama Kelas <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="field-input" required placeholder="Contoh: X PPLG 1"
                           x-data="{ name: '{{ old('name') }}', detected: '' }"
                           x-model="name"
                           x-effect="
                               const m = name.match(/^(XII|XI|X)\s+(.*)/i);
                               detected = m ? 'Tingkat: ' + (m[1].toUpperCase()) + ' = ' + (m[1].toUpperCase() === 'X' ? '10' : (m[1].toUpperCase() === 'XI' ? '11' : '12')) + ' Kelas' : '';
                           ">
                    <div x-show="detected" x-text="detected" style="margin-top:4px;font-size:.75rem;color:var(--tertiary);font-weight:600"></div>
                    <span class="help-text">Gunakan X, XI, atau XII untuk tingkat. Huruf besar X otomatis = kelas 10.</span>
                </div>
                <div class="field"><label class="field-label">Tahun Ajaran <span class="text-danger">*</span></label>
                    <select name="academic_year_id" class="field-input" required>
                        <option value="">Pilih tahun ajaran...</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ old('academic_year_id')==$year->id?'selected':'' }}>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="field-label">Wali Kelas</label><input type="text" name="homeroom_teacher" value="{{ old('homeroom_teacher') }}" class="field-input" placeholder="Opsional..."></div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('classes.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="save" class="icon-sm"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
