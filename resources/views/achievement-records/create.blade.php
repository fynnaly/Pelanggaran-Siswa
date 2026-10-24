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
        <div class="card card-form" x-data="recordForm(@js($selectedStudent))">
            @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)"><i data-lucide="alert-circle" class="icon-sm"></i> {{ $errors->first() }}</div>@endif
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
                    <select name="achievement_category_id" class="field-input" required x-model="selectedCategoryId" @change="updatePoinPreview()">
                        <option value="">Pilih kategori...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" data-points="{{ $cat->points }}" {{ old('achievement_category_id')==$cat->id?'selected':'' }}>{{ $cat->code }} — {{ $cat->name }} (+{{ $cat->points }} poin)</option>
                        @endforeach
                    </select>
                    <span class="help-text">Poin otomatis dari kategori — <strong x-text="poinPreview"></strong></span>
                </div>
                <div class="field" x-show="openCases.length > 0" x-cloak>
                    <label class="field-label">Tautkan ke Kasus Pelanggaran</label>
                    <select name="discipline_case_id" class="field-input" x-model="selectedCaseId" @change="checkCaseMatch()">
                        <option value="">Prestasi biasa (tanpa penebusan)</option>
                        <template x-for="c in openCases" :key="c.id">
                            <option :value="c.id" x-text="c.label"></option>
                        </template>
                    </select>
                    <span class="help-text" x-show="!caseHint">Siswa ini punya kasus terbuka — pilih bila prestasi ini menebus salah satunya.</span>
                    <span class="help-text" x-show="caseHint" x-text="caseHint" style="color:var(--tertiary);font-weight:600"></span>
                </div>
                <div class="field"><label class="field-label">Nama Event / Pencapaian <span class="text-danger">*</span></label>
                    <input type="text" name="event_name" class="field-input" maxlength="150" required value="{{ old('event_name') }}" placeholder="Contoh: Lomba Cerdas Cermat Tingkat Provinsi">
                    <span class="help-text">Nama event atau pencapaian yang diraih siswa</span>
                </div>
                @error('event_name')<span class="text-danger text-sm">{{ $message }}</span>@enderror
                <div class="field"><label class="field-label">Tanggal Event</label>
                    <input type="date" name="event_date" class="field-input" value="{{ old('event_date') }}">
                    <span class="help-text">Kosongkan untuk hari ini</span>
                </div>
                @error('event_date')<span class="text-danger text-sm">{{ $message }}</span>@enderror
                <div class="field"><label class="field-label">Tingkat / Level</label>
                    <input type="text" name="achievement_level" class="field-input" maxlength="50" value="{{ old('achievement_level') }}" placeholder="Contoh: Provinsi, Nasional">
                    <span class="help-text">Tingkat pencapaian (opsional)</span>
                </div>
                @error('achievement_level')<span class="text-danger text-sm">{{ $message }}</span>@enderror
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
        function recordForm(preset){return{
            results:[],selectedStudentId:preset?.id||null,selectedStudentName:preset?.full_name||'',
            openCases:[],selectedCaseId:'',caseHint:'',selectedCategoryId:'',poinPreview:'belum pilih kategori',
            searchTimeout:null,
            searchStudent(q){clearTimeout(this.searchTimeout);if(q.length<2){this.results=[];return}this.searchTimeout=setTimeout(async()=>{try{const r=await fetch('/students/search?q='+encodeURIComponent(q));this.results=Array.isArray(r.data)?r.data:await r.json()||[]}catch(e){this.results=[]}},250)},
            selectStudent(s){this.selectedStudentId=s.id;this.selectedStudentName=s.full_name;this.results=[];document.querySelector('input[name=student_search]').value='';this.loadOpenCases()},
            clearStudent(){this.selectedStudentId=null;this.selectedStudentName='';this.openCases=[];this.selectedCaseId='';this.caseHint=''},
            async loadOpenCases(){this.openCases=[];this.selectedCaseId='';this.caseHint='';if(!this.selectedStudentId)return;try{const r=await fetch('/students/'+this.selectedStudentId+'/open-cases',{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});this.openCases=await r.json()||[]}catch(e){this.openCases=[]}},
            updatePoinPreview(){const el=document.querySelector('select[name=achievement_category_id] option:checked');const p=el?.dataset?.points;this.poinPreview=p?'+'+p+' poin':'belum pilih kategori';this.checkCaseMatch()},
            checkCaseMatch(){this.caseHint='';if(!this.selectedCaseId||!this.selectedCategoryId)return;const c=this.openCases.find(x=>String(x.id)===String(this.selectedCaseId));if(!c||!c.required_achievement?.length)return;const need=c.required_achievement.map(a=>a.code).join(', ');this.caseHint='Kasus ini mewajibkan: '+need+'. Tanpa prestasi terverifikasi, poin tertahan.'}
        }}
    </script>
</x-app-layout>
