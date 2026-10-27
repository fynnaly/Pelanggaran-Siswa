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
                <div class="field">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-xs)">
                        <label class="field-label" style="margin:0">Siswa Penerima Prestasi <span class="text-danger">*</span></label>
                        <div x-show="selectedStudents.length > 0" x-cloak style="display:flex;align-items:center;gap:8px">
                            <span class="text-xs text-muted" x-text="selectedStudents.length + ' siswa terpilih'"></span>
                            <button type="button" @click="clearAllStudents()" class="btn btn-sm" style="padding:2px 8px;font-size:0.75rem;background:var(--ds);color:var(--dt);border:1px solid var(--border)">
                                Kosongkan Semua
                            </button>
                        </div>
                    </div>

                    <!-- Autocomplete Search Siswa Tunggal -->
                    <div style="position:relative">
                        <input type="text" name="student_search" class="field-input" placeholder="Cari nama atau NIS siswa untuk ditambahkan..." @input="searchStudent($event.target.value)" autocomplete="off">
                        <div x-show="results.length > 0" x-cloak style="background:var(--neutral);border:1px solid var(--border);border-radius:var(--r-sm);margin-top:4px;max-height:200px;overflow-y:auto;position:absolute;left:0;right:0;top:100%;z-index:30;box-shadow:var(--sh-md)">
                            <template x-for="s in results" :key="s.id">
                                <div @click="selectStudent(s)" style="padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--border);font-size:.875rem;display:flex;justify-content:space-between;align-items:center">
                                    <div>
                                        <strong x-text="s.full_name"></strong>
                                        <span class="text-muted text-xs" x-text="' (' + (s.nis || '-') + ')'"></span>
                                    </div>
                                    <span class="badge badge-neutral text-xs" x-text="s.kelas || '-'"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Input Cepat Kolektif Seluruh Siswa Rombel / Kelas -->
                    @if(isset($classes) && $classes->isNotEmpty())
                    <div style="display:flex;align-items:center;gap:var(--sp-sm);margin-top:var(--sp-sm)">
                        <span class="text-xs text-muted" style="white-space:nowrap">Atau apresiasi satu kelas:</span>
                        <select class="field-input" style="padding:4px 8px;font-size:.8125rem;width:auto;flex:1" @change="loadClassStudents($event.target.value); $event.target.value = ''">
                            <option value="">Pilih rombel / kelas...</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- Daftar Siswa yang Telah Ditambahkan -->
                    <div x-show="selectedStudents.length > 0" x-cloak style="margin-top:var(--sp-md);display:flex;flex-wrap:wrap;gap:6px;max-height:160px;overflow-y:auto;padding:8px;background:var(--surface);border:1px solid var(--border);border-radius:var(--r-sm)">
                        <template x-for="s in selectedStudents" :key="s.id">
                            <span class="badge" style="background:var(--bs);color:var(--bt);padding:4px 10px;display:inline-flex;align-items:center;gap:6px">
                                <span x-text="s.full_name + ' (' + (s.kelas || '-') + ')'"></span>
                                <a href="javascript:void(0)" @click="removeStudent(s.id)" style="color:var(--bt);line-height:1"><i data-lucide="x" style="width:14px;height:14px"></i></a>
                            </span>
                        </template>
                    </div>

                    <!-- Hidden inputs untuk setiap siswa yang terpilih -->
                    <template x-for="s in selectedStudents" :key="'hid_'+s.id">
                        <input type="hidden" name="student_ids[]" :value="s.id">
                    </template>
                    <input type="hidden" name="student_id" :value="selectedStudents[0]?.id || ''">

                    <span class="help-text">Dapat memilih siswa tunggal, beberapa siswa, atau apresiasi seluruh siswa satu rombel/kelas</span>
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

                <div class="field" x-show="selectedStudents.length === 1 && openCases.length > 0" x-cloak>
                    <label class="field-label">Tautkan ke Kasus Pelanggaran</label>
                    <select name="discipline_case_id" class="field-input" x-model="selectedCaseId" @change="checkCaseMatch()">
                        <option value="">Prestasi biasa (tanpa penebusan)</option>
                        <template x-for="c in openCases" :key="c.id">
                            <option :value="c.id" x-text="c.label"></option>
                        </template>
                    </select>
                    <span class="help-text" x-show="!caseHint">Siswa ini memiliki kasus aktif — pilih jika prestasi ini sebagai pemulihan/penebusan.</span>
                    <span class="help-text" x-show="caseHint" x-text="caseHint" style="color:var(--tertiary);font-weight:600"></span>
                </div>

                <div class="field"><label class="field-label">Nama Event / Pencapaian <span class="text-danger">*</span></label>
                    <input type="text" name="event_name" class="field-input" maxlength="150" required value="{{ old('event_name') }}" placeholder="Contoh: Juara 1 Kebersihan Rombel / Lomba LKS">
                    <span class="help-text">Nama event, kegiatan, atau pencapaian yang diraih</span>
                </div>
                @error('event_name')<span class="text-danger text-sm">{{ $message }}</span>@enderror

                <div class="field"><label class="field-label">Tanggal Event</label>
                    <input type="date" name="event_date" class="field-input" value="{{ old('event_date', date('Y-m-d')) }}">
                    <span class="help-text">Tanggal pelaksanaan kegiatan / pengumuman</span>
                </div>
                @error('event_date')<span class="text-danger text-sm">{{ $message }}</span>@enderror

                <div class="field"><label class="field-label">Tingkat / Level</label>
                    <input type="text" name="achievement_level" class="field-input" maxlength="50" value="{{ old('achievement_level') }}" placeholder="Contoh: Sekolah, Kota, Provinsi, Nasional">
                    <span class="help-text">Skala pencapaian (opsional)</span>
                </div>
                @error('achievement_level')<span class="text-danger text-sm">{{ $message }}</span>@enderror

                <div class="field">
                    <label class="field-label">Keterangan Tambahan</label>
                    <textarea name="description" class="field-input" rows="4" placeholder="Ceritakan detail prestasi atau catatan pembina...">{{ old('description') }}</textarea>
                </div>

                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('achievement-records.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center" :disabled="selectedStudents.length === 0">
                        <i data-lucide="save" class="icon-sm"></i> Simpan Rekam Prestasi
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
    <script>
        function recordForm(preset){return{
            results:[],
            selectedStudents: preset?.id ? [preset] : [],
            openCases:[],
            selectedCaseId:'',
            caseHint:'',
            selectedCategoryId:'',
            poinPreview:'belum pilih kategori',
            searchTimeout:null,

            searchStudent(q){
                clearTimeout(this.searchTimeout);
                if(q.length<2){this.results=[];return}
                this.searchTimeout=setTimeout(async()=>{
                    try{
                        const r=await fetch('/students/search?q='+encodeURIComponent(q));
                        this.results=Array.isArray(r.data)?r.data:await r.json()||[];
                    }catch(e){this.results=[]}
                },250);
            },
            selectStudent(s){
                if(!this.selectedStudents.some(item=>item.id===s.id)){
                    this.selectedStudents.push(s);
                }
                this.results=[];
                const inp=document.querySelector('input[name=student_search]');
                if(inp)inp.value='';
                if(this.selectedStudents.length===1){
                    this.loadOpenCases(this.selectedStudents[0].id);
                } else {
                    this.openCases=[];
                    this.selectedCaseId='';
                }
                this.$nextTick(()=>lucide.createIcons());
            },
            removeStudent(id){
                this.selectedStudents=this.selectedStudents.filter(s=>s.id!==id);
                if(this.selectedStudents.length===1){
                    this.loadOpenCases(this.selectedStudents[0].id);
                } else {
                    this.openCases=[];
                    this.selectedCaseId='';
                }
            },
            clearAllStudents(){
                this.selectedStudents=[];
                this.openCases=[];
                this.selectedCaseId='';
            },
            async loadClassStudents(classId){
                if(!classId)return;
                try{
                    const r=await fetch('/students/search?class_id='+encodeURIComponent(classId));
                    const list=Array.isArray(r.data)?r.data:await r.json()||[];
                    list.forEach(s=>{
                        if(!this.selectedStudents.some(item=>item.id===s.id)){
                            this.selectedStudents.push(s);
                        }
                    });
                    this.openCases=[];
                    this.selectedCaseId='';
                    this.$nextTick(()=>lucide.createIcons());
                }catch(e){}
            },
            async loadOpenCases(studentId){
                this.openCases=[];this.selectedCaseId='';this.caseHint='';
                if(!studentId)return;
                try{
                    const r=await fetch('/students/'+studentId+'/open-cases',{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
                    this.openCases=await r.json()||[];
                }catch(e){this.openCases=[]}
            },
            updatePoinPreview(){
                const el=document.querySelector('select[name=achievement_category_id] option:checked');
                const p=el?.dataset?.points;
                this.poinPreview=p?'+'+p+' poin':'belum pilih kategori';
                this.checkCaseMatch();
            },
            checkCaseMatch(){
                this.caseHint='';
                if(!this.selectedCaseId||!this.selectedCategoryId)return;
                const c=this.openCases.find(x=>String(x.id)===String(this.selectedCaseId));
                if(!c||!c.required_achievement?.length)return;
                const need=c.required_achievement.map(a=>a.code).join(', ');
                this.caseHint='Kasus ini mewajibkan: '+need+'. Tanpa prestasi terverifikasi, poin tertahan.';
            },
            init(){
                if(this.selectedStudents.length===1){
                    this.loadOpenCases(this.selectedStudents[0].id);
                }
                this.$nextTick(()=>lucide.createIcons());
            }
        }}
    </script>
</x-app-layout>
