<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Kasus / Tambah</div>
        <div class="page-header">
            <div><h1>Buat Kasus Baru</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('discipline-cases.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card" style="max-width:600px" x-data="caseForm()">
            @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
            <form action="{{ route('discipline-cases.store') }}" method="POST">
                @csrf
                <div class="field"><label class="field-label">Siswa <span class="text-danger">*</span></label>
                    <input type="text" name="student_search" class="field-input" placeholder="Ketik nama atau NISN siswa..." @input="searchStudent($event.target.value)" autocomplete="off">
                    <input type="hidden" name="student_id" :value="selectedStudentId">
                    <div x-show="results.length > 0 && !selectedStudentId" style="background:var(--neutral);border:1px solid var(--border);border-radius:var(--r-sm);margin-top:4px;max-height:180px;overflow-y:auto;position:relative;z-index:10">
                        <template x-for="s in results" :key="s.id">
                            <div @click="selectStudent(s)" style="padding:8px 12px;cursor:pointer;border-bottom:1px solid var(--border);font-size:.875rem">
                                <strong x-text="s.full_name"></strong>
                                <span class="text-muted" x-text="' - ' + (s.school_class?.name || '')"></span>
                            </div>
                        </template>
                    </div>
                    <div x-show="selectedStudentId" style="margin-top:6px"><span class="badge badge-success"><i data-lucide="user" style="width:12px;height:12px"></i> <span x-text="selectedStudentName"></span></span> <button type="button" class="text-sm" @click="clearStudent()" style="color:var(--danger);border:none;background:none;cursor:pointer">[ Hapus ]</button></div>
                    <span class="help-text">Pilih nama siswa yang melakukan pelanggaran</span>
                </div>
                <div class="field"><label class="field-label">Kategori <span class="text-danger">*</span></label>
                    <select name="violation_category_id" class="field-input" required>
                        <option value="">Pilih kategori...</option>
                        @foreach($violationCategories as $cat)
                            <option value="{{ $cat->id }}" {{ old('violation_category_id')==$cat->id?'selected':'' }}>{{ $cat->name }} ({{ $cat->points }} poin)</option>
                        @endforeach
                    </select>
                    <span class="help-text">Pilih jenis pelanggaran yang dilakukan siswa</span>
                </div>
                <div class="field"><label class="field-label">Pelapor <span class="text-danger">*</span></label>
                    <select name="reporter_id" class="field-input" required>
                        <option value="">Pilih pelapor...</option>
                        @foreach($reporters as $rep)
                            <option value="{{ $rep->id }}" {{ old('reporter_id')==$rep->id?'selected':'' }}>{{ $rep->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="field-label">Keterangan / Kronologi</label><textarea name="notes" class="field-input" rows="4" placeholder="Ceritakan kronologi kejadian (waktu, tempat, keadaan)...">{{ old('notes') }}</textarea></div>
                <div class="checkbox-row" style="margin-bottom:var(--sp-lg)"><input type="checkbox" name="confirm_violation" value="1" class="checkbox-input" required><span class="text-sm">Saya memastikan data yang diisi sudah benar.</span></div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('discipline-cases.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="file-plus" class="icon-sm"></i> Simpan Kasus</button>
                </div>
            </form>
        </div>

        {{-- Rekomendasi Pemulihan Poin --}}
        <div class="card" style="max-width:600px;margin-top:var(--sp-lg)">
            <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="rotate-ccw" class="icon"></i> Rekomendasi Pemulihan Poin</h2>
            <p class="text-sm text-muted" style="margin-bottom:var(--sp-md)">Setiap pelanggaran mengurangi poin siswa. Poin dapat dipulihkan melalui pencapaian/prestasi berikut:</p>
            @php
                $achievements = \App\Models\AchievementCategory::where('status', 'active')->orderByDesc('points')->get();
            @endphp
            @if($achievements->count())
                <div style="display:grid;gap:var(--sp-sm)">
                    @foreach($achievements as $ach)
                        <div style="display:flex;align-items:center;gap:var(--sp-md);padding:var(--sp-md);background:var(--surface);border:1px solid var(--border);border-radius:var(--r-sm)">
                            <div style="width:40px;height:40px;border-radius:var(--r-sm);background:var(--bs);color:var(--bt);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i data-lucide="trophy" style="width:18px;height:18px"></i></div>
                            <div style="flex:1;min-width:0">
                                <div style="font-weight:600;font-size:.875rem">{{ $ach->name }}</div>
                                <div class="text-sm text-muted">{{ $ach->code }} &middot; +{{ $ach->points }} poin per pencapaian</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r-sm);padding:var(--sp-md);text-align:center">
                    <p class="text-sm text-muted">Belum ada kategori pencapaian aktif.</p>
                    <a href="{{ route('achievement-categories.create') }}" class="btn btn-secondary btn-sm" style="margin-top:var(--sp-sm)"><i data-lucide="plus" class="icon-sm"></i> Buat Kategori Pencapaian</a>
                </div>
            @endif
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
    <script>
        function caseForm(){return{results:[],selectedStudentId:null,selectedStudentName:'',searchTimeout:null,searchStudent(q){clearTimeout(this.searchTimeout);if(q.length<2){this.results=[];return}this.searchTimeout=setTimeout(async()=>{try{const r=await fetch('/api/students/search?q='+encodeURIComponent(q));this.results=(await r.json()).data||[]}catch(e){this.results=[]}},250)},selectStudent(s){this.selectedStudentId=s.id;this.selectedStudentName=s.full_name;this.results=[];document.querySelector('input[name=student_search]').value='';},clearStudent(){this.selectedStudentId=null;this.selectedStudentName='';}}}
    </script>
</x-app-layout>