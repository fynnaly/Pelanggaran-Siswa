<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div><h1>Buat Kasus Baru</h1></div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('kasus-pelanggaran.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left" class="icon-sm"></i> Kembali</a>
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        <div class="card card-form" x-data="caseForm()">
            @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif
            <form action="{{ route('kasus-pelanggaran.store') }}" method="POST">
                @csrf
                <div class="field">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-xs)">
                        <label class="field-label" style="margin:0">Siswa Pelanggar <span class="text-danger">*</span></label>
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
                    <div style="display:flex;align-items:center;gap:var(--sp-sm);margin-top:var(--sp-sm)">
                        <span class="text-xs text-muted" style="white-space:nowrap">Atau tambah satu kelas:</span>
                        <select class="field-input" style="padding:4px 8px;font-size:.8125rem;width:auto;flex:1" @change="loadClassStudents($event.target.value); $event.target.value = ''">
                            <option value="">Pilih rombel / kelas...</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

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

                    <span class="help-text">Dapat memilih satu siswa atau menambahkan banyak siswa sekaligus (kolektif)</span>
                </div>

                <div class="field"><label class="field-label">Tanggal Kejadian</label>
                    <input type="date" name="incident_date" class="field-input" value="{{ old('incident_date', date('Y-m-d')) }}">
                    <span class="help-text">Tanggal terjadinya pelanggaran</span>
                </div>
                @error('incident_date')<span class="text-danger text-sm">{{ $message }}</span>@enderror

                <div class="field">
                    <label class="field-label">Kelas Saat Kejadian</label>
                    <div style="padding:10px 14px;background:var(--surface);border:1px solid var(--border);border-radius:var(--r-sm);color:var(--on-surface-muted);font-size:.875rem;display:flex;align-items:center;gap:8px">
                        <i data-lucide="check-circle" class="icon-sm" style="color:var(--tertiary);flex-shrink:0"></i>
                        <span>Otomatis terhubung dengan kelas aktif masing-masing siswa pada tahun ajaran berjalan.</span>
                    </div>
                </div>

                <div class="field"><label class="field-label">Kategori <span class="text-danger">*</span></label>
                    <select class="field-input" x-model="groupId" @change="onGroupChange()">
                        <option value="">Pilih kategori...</option>
                        @foreach($categoryGroups as $group)
                            <option value="{{ $group->id }}" {{ $selectedGroupId==$group->id?'selected':'' }}>{{ $group->name }}@if($group->children->isEmpty()) (induk tunggal)@endif</option>
                        @endforeach
                    </select>
                    <span class="help-text">Kategori besar, misal Kehadiran atau Penampilan</span>
                </div>

                <div class="field" x-show="groupId" x-cloak><label class="field-label">Sub Kategori <span class="text-danger">*</span></label>
                    <select name="violation_category_id" id="leafSelect" class="field-input" x-model="leafId" @change="onLeafChange()" required>
                        <option value="">Pilih sub kategori...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" data-group="{{ $cat->parent_id ?? $cat->id }}" data-points="{{ $cat->points }}" data-severity="{{ $cat->severity }}" {{ old('violation_category_id')==$cat->id?'selected':'' }}>{{ $cat->code }} — {{ $cat->name }} ({{ implode('/', $cat->tierPoints()) }} poin)</option>
                        @endforeach
                    </select>
                    <span class="help-text">Jenis pelanggaran spesifik yang dilakukan</span>
                </div>
                @error('violation_category_id')<span class="text-danger text-sm">{{ $message }}</span>@enderror

                {{-- Panel otomatis: tampil setelah sub kategori dipilih --}}
                <div x-show="leafId" x-cloak style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r-sm);padding:var(--sp-md);margin-bottom:var(--sp-md)">
                    <div style="display:flex;gap:var(--sp-md);flex-wrap:wrap;margin-bottom:var(--sp-sm)">
                        <span class="text-sm"><span class="text-muted">Poin ke-1/2/3:</span> <strong x-text="leafTiers()" style="color:var(--danger)"></strong></span>
                        <span class="text-sm"><span class="text-muted">Tingkat:</span> <strong x-text="leafSeverity()"></strong></span>
                    </div>
                    <div x-show="meta && meta.sanksi" style="margin-bottom:var(--sp-sm)">
                        <span class="text-sm text-muted">Sanksi:</span>
                        <div class="text-sm" style="font-weight:600" x-text="meta ? meta.sanksi : ''"></div>
                    </div>
                    <div x-show="meta && meta.is_zero_tolerance" class="text-sm" style="color:var(--danger);font-weight:600">
                        Zero tolerance — Program Pembinaan Intensif, tidak bisa ditebus poin prestasi biasa.
                    </div>
                </div>

                <div class="field">
                    <label class="field-label">Pelapor <span class="text-danger">*</span></label>
                    <input type="text" class="field-input cursor-not-allowed bg-slate-100 dark:bg-zinc-800 text-slate-500" value="{{ auth()->user()->name }} ({{ ucfirst(auth()->user()->getRoleNames()->first() ?? 'Staf') }})" disabled style="background:var(--surface);cursor:not-allowed">
                    <input type="hidden" name="reporter_id" value="{{ auth()->id() }}">
                    <span class="help-text">Laporan otomatis tercatat atas nama Anda sebagai staf sekolah pelapor</span>
                </div>

                <div class="field">
                    <label class="field-label">Keterangan / Kronologi <span class="text-danger">*</span></label>
                    <textarea name="notes" class="field-input" rows="4" placeholder="Ceritakan kronologi kejadian (waktu, tempat, keadaan)..." required>{{ old('notes') }}</textarea>
                </div>
                @error('notes')<span class="text-danger text-sm">{{ $message }}</span>@enderror

                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('kasus-pelanggaran.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center" :disabled="selectedStudents.length === 0">
                        <i data-lucide="file-plus" class="icon-sm"></i> Simpan Kasus
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
    <script>
        function caseForm(){return{
            results:[],
            selectedStudents: @js($selectedStudent ? [$selectedStudent] : []),
            searchTimeout:null,
            groupId:@js($selectedGroupId),
            leafId:@js(old('violation_category_id')),
            meta:null,
            metaLoading:false,

            onGroupChange(){this.leafId='';this.meta=null;this.filterSubs();},
            filterSubs(){
                const sel=document.getElementById('leafSelect');
                if(!sel)return;
                let match=null;
                sel.querySelectorAll('option[data-group]').forEach(o=>{
                    const ok=o.dataset.group===String(this.groupId??'');
                    o.hidden=!ok;
                    o.style.display=ok?'':'none';
                    if(ok&&!match)match=o;
                });
                if(match&&String(match.dataset.group)===String(this.groupId)&&!this.hasSiblingSubs()){
                    this.leafId=match.value;
                    this.onLeafChange();
                }
            },
            hasSiblingSubs(){
                const sel=document.getElementById('leafSelect');
                if(!sel)return false;
                let n=0;
                sel.querySelectorAll('option[data-group]').forEach(o=>{
                    if(o.dataset.group===String(this.groupId??'')&&!o.hidden)n++
                });
                return n>1;
            },
            leafPoints(){
                const o=document.querySelector('#leafSelect option[value="'+this.leafId+'"]');
                return o?o.dataset.points:'-';
            },
            leafTiers(){
                if(this.meta&&Array.isArray(this.meta.tiers)&&this.meta.tiers.length)return this.meta.tiers.join(' / ');
                return this.leafPoints();
            },
            leafSeverity(){
                const o=document.querySelector('#leafSelect option[value="'+this.leafId+'"]');
                return o?o.dataset.severity:'-';
            },
            async onLeafChange(){
                this.meta=null;
                if(!this.leafId){return;}
                this.metaLoading=true;
                try{
                    const r=await fetch('/violation-categories/'+this.leafId+'/rekomendasi');
                    this.meta=r.ok?await r.json():null;
                }catch(e){this.meta=null}
                finally{this.metaLoading=false;this.$nextTick(()=>lucide.createIcons());}
            },
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
                this.$nextTick(()=>lucide.createIcons());
            },
            removeStudent(id){
                this.selectedStudents=this.selectedStudents.filter(s=>s.id!==id);
            },
            clearAllStudents(){
                this.selectedStudents=[];
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
                    this.$nextTick(()=>lucide.createIcons());
                }catch(e){}
            },
            init(){
                this.$nextTick(()=>{
                    this.filterSubs();
                    if(this.leafId)this.onLeafChange();
                    lucide.createIcons();
                });
            }
        }}
    </script>
</x-app-layout>
