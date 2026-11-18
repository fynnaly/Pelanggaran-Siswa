<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Edit: {{ $achievementCategory->code }}</h1>
                <p class="text-muted text-sm">Perbarui informasi kategori prestasi.</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('achievement-categories.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card card-form" x-data="{ parentSelected: {{ filled(old('parent_id', $achievementCategory->parent_id)) ? 'true' : 'false' }} }">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
            <form action="{{ route('achievement-categories.update', $achievementCategory) }}" method="POST">
                @csrf @method('PUT')

                <div class="field">
                    <label class="field-label">Kode <span class="text-danger">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $achievementCategory->code) }}" class="field-input" required maxlength="20">
                    <span class="help-text">Kode unik, maksimal 20 karakter.</span>
                </div>

                <div class="field">
                    <label class="field-label">Nama <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $achievementCategory->name) }}" class="field-input" required maxlength="150">
                </div>

                <div class="field">
                    <label class="field-label">Induk Kategori</label>
                    <select name="parent_id" class="field-input" @change="parentSelected = $event.target.value !== ''">
                        <option value="">Tidak ada — kategori induk</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_id', $achievementCategory->parent_id)==$parent->id?'selected':'' }}>{{ $parent->code }} — {{ $parent->name }}</option>
                        @endforeach
                    </select>
                    <span class="help-text">Kosongkan bila ini kategori besar; isi bila ini sub-kategori.</span>
                </div>

                <div class="field">
                    <label class="field-label">Poin <span class="text-danger">*</span></label>
                    <input type="number" name="points" value="{{ old('points', $achievementCategory->points) }}" class="field-input" required min="0" max="1000">
                    <span class="help-text">Kategori induk bernilai 0; sub-kategori harus bernilai minimal 1.</span>
                </div>

                <div class="field">
                    <label class="field-label">Grup Kategori</label>
                    <input type="text" name="group_type" value="{{ old('group_type', $achievementCategory->group_type) }}" class="field-input" list="achievement-group-types" maxlength="50" placeholder="Contoh: akademik">
                    <datalist id="achievement-group-types">
                        @foreach($groupTypes as $type)
                            <option value="{{ $type }}"></option>
                        @endforeach
                    </datalist>
                    <span class="help-text">Dipakai untuk pengelompokan laporan. Pilih dari daftar atau tulis sendiri.</span>
                </div>

                <div class="field" x-show="parentSelected" x-cloak>
                    <label class="field-label">Padanan Pelanggaran</label>
                    <span class="help-text">Sub-kategori pelanggaran dengan nama kategori induk yang sama akan dipadankan otomatis. Poin pemulihan mengikuti poin prestasi.</span>
                </div>

                <details style="margin:var(--sp-lg) 0;border:1px solid var(--border);border-radius:var(--r-md);padding:var(--sp-md)" @if($achievementCategory->description || $achievementCategory->max_points_per_event || $achievementCategory->effective_date || $achievementCategory->expiry_date) open @endif>
                    <summary style="cursor:pointer;font-weight:600;font-size:.875rem">Pengaturan lanjutan (opsional)</summary>
                    <div style="margin-top:var(--sp-md)">
                        <div class="field">
                            <label class="field-label">Deskripsi</label>
                            <textarea name="description" class="field-input" rows="3" placeholder="Penjelasan singkat kategori ini...">{{ old('description', $achievementCategory->description) }}</textarea>
                        </div>

                        <div class="field">
                            <label class="field-label">Batas Poin per Kegiatan</label>
                            <input type="number" name="max_points_per_event" value="{{ old('max_points_per_event', $achievementCategory->max_points_per_event) }}" class="field-input" min="0" max="1000" placeholder="Kosongkan bila tidak dibatasi">
                            <span class="help-text">Batas maksimal poin yang boleh diperoleh dari satu kegiatan.</span>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:var(--sp-sm)">
                            <div class="field">
                                <label class="field-label">Berlaku Mulai</label>
                                <input type="date" name="effective_date" value="{{ old('effective_date', $achievementCategory->effective_date?->format('Y-m-d')) }}" class="field-input">
                                <span class="help-text">Kosongkan bila langsung berlaku.</span>
                            </div>
                            <div class="field">
                                <label class="field-label">Berakhir Pada</label>
                                <input type="date" name="expiry_date" value="{{ old('expiry_date', $achievementCategory->expiry_date?->format('Y-m-d')) }}" class="field-input">
                                <span class="help-text">Kosongkan bila berlaku tanpa batas.</span>
                            </div>
                        </div>
                    </div>
                </details>

                <div class="field">
                    <label class="field-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="field-input" required>
                        <option value="active" {{ old('status', $achievementCategory->status) === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="draft" {{ old('status', $achievementCategory->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                </div>

                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('achievement-categories.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="save" class="icon-sm"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
