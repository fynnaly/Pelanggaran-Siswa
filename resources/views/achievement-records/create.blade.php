<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Tambah Rekam Prestasi</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('achievement-records.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card card-form" x-data="recordForm()">
            @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
            <form action="{{ route('achievement-records.store') }}" method="POST">
                @csrf
                <div class="field"><label class="field-label">Siswa <span class="text-danger">*</span></label>
                    <input type="text" name="student_search" class="field-input" placeholder="Ketik nama atau NIS siswa..." @input="searchStudent($event.target.value)" autocomplete="off" x-show="!selectedStudentId">
                    <input type="hidden" name="student_id" :value="selectedStudentId">
                    <div x-show="results.length > 0 && !selectedStudentId" style="background:var(--neutral);border:1px solid var(--border);border-radius:var(--r-sm);margin-top:4px;max-height:180px;overflow-y:auto;position:relative;z-index:10">
                        <template x-for="s in results" :key="s.id">
                            <div @click="selectStudent(s)" style="padding:8px 12px;cursor:pointer;border-bottom:1px solid var(--border);font-size:.875rem">
                                <strong x-text="s.full_name"></strong>
                                <span class="text-muted" x-text="' - ' + (s.school_class?.name || s.kelas || '')"></span>
                            </div>
                        </template>
                    </div>
                    <div x-show="selectedStudentId" style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-md)">
                        <span class="badge" style="background:var(--bs);color:var(--bt)"><i data-lucide="user" class="icon-sm"></i> <span x-text="selectedStudentName"></span></span>
                        <a href="javascript:void(0)" class="text-sm" style="color:var(--on-surface-muted);white-space:nowrap;flex-shrink:0" @click="clearStudent()"><i data-lucide="x" class="icon-sm"></i></a>
                    </div>
                    <span class="help-text">Pilih siswa yang meraih prestasi</span>
                </div>
                <div class="field"><label class="field-label">Kategori Prestasi <span class="text-danger">*</span></label>
                    <select name="achievement_category_id" class="field-input" required>
                        <option value="">Pilih kategori...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('achievement_category_id')==$cat->id?'selected':'' }}>{{ $cat->name }} (+{{ $cat->points }} poin)</option>
                        @endforeach
                    </select>
                    <span class="help-text">Pilih jenis prestasi yang diraih siswa</span>
                </div>
                <div class="field"><label class="field-label">Keterangan</label><textarea name="description" class="field-input" rows="4" placeholder="Ceritakan kronologi prestasi (waktu, event, pencapaian)...">{{ old('description') }}</textarea></div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('achievement-records.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="save" class="icon-sm"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
    <script>
        function recordForm(){return{results:[],selectedStudentId:null,selectedStudentName:'',searchTimeout:null,searchStudent(q){clearTimeout(this.searchTimeout);if(q.length<2){this.results=[];return}this.searchTimeout=setTimeout(async()=>{try{const r=await fetch('/students/search?q='+encodeURIComponent(q));this.results=Array.isArray(r.data)?r.data:await r.json()||[]}catch(e){this.results=[]}},250)},selectStudent(s){this.selectedStudentId=s.id;this.selectedStudentName=s.full_name;this.results=[];document.querySelector('input[name=student_search]').value='';},clearStudent(){this.selectedStudentId=null;this.selectedStudentName='';}}}
    </script>
</x-app-layout>
