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
                <div class="field"><label class="field-label">Siswa <span class="text-danger">*</span></label>
                    <input type="text" name="student_search" class="field-input" placeholder="Ketik nama atau NISN siswa..." @input="searchStudent($event.target.value)" autocomplete="off" x-show="!selectedStudentId">
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
                    <span class="help-text">Pilih nama siswa yang melakukan pelanggaran</span>
                </div>
                <div class="field"><label class="field-label">Tanggal Kejadian</label>
                                    <input type="date" name="incident_date" class="field-input" value="{{ old('incident_date') }}">
                                    <span class="help-text">Kosongkan untuk hari ini</span>
                                </div>
                                @error('incident_date')<span class="text-danger text-sm">{{ $message }}</span>@enderror
                                <div class="field"><label class="field-label">Kelas Saat Kejadian</label>
                                    <select name="class_id" class="field-input">
                                        <option value="">Otomatis dari enrollment siswa</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}" {{ old('class_id')==$class->id?'selected':'' }}>{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="help-text">Kosongkan untuk mengambil kelas dari enrollment siswa saat kejadian. Pilih manual hanya bila siswa belum terdaftar di kelas manapun.</span>
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
                                    <span class="help-text">Isi dari kategori yang dipilih — jenis pelanggaran spesifiknya</span>
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
                                    <div x-show="meta && !meta.is_zero_tolerance && meta.recovery && meta.recovery.length">
                                        <div class="text-sm text-muted" style="margin-bottom:6px">Rekomendasi Pemulihan Poin (otomatis):</div>
                                        <div class="recovery-cards">
                                            <template x-for="r in (meta ? meta.recovery : [])" :key="r.code">
                                                <div class="recovery-card">
                                                    <div class="recovery-card-icon"><i data-lucide="trophy" style="width:18px;height:18px"></i></div>
                                                    <div class="recovery-card-body">
                                                        <div class="recovery-card-title" x-text="r.name"></div>
                                                        <div class="text-sm text-muted"><span x-text="r.code"></span> &middot; +<span x-text="r.poin"></span> poin penebus</div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div x-show="meta && !meta.is_zero_tolerance && (!meta.recovery || !meta.recovery.length)">
                                        <span class="text-sm text-muted">Belum ada rekomendasi padanan untuk sub kategori ini.</span>
                                    </div>
                                </div>
                                @php $isAdmin = auth()->user()->role === 'admin'; @endphp
                                @if($isAdmin)
                                <div class="field"><label class="field-label">Pelapor <span class="text-danger">*</span></label>
                                    <select name="reporter_id" class="field-input" required>
                                        <option value="">Pilih pelapor...</option>
                                        @foreach($reporters as $rep)
                                            <option value="{{ $rep->id }}" {{ old('reporter_id')==$rep->id?'selected':'' }}>{{ $rep->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @else
                                <input type="hidden" name="reporter_id" value="{{ auth()->id() }}">
                                @endif
                                <div class="field"><label class="field-label">Keterangan / Kronologi <span class="text-danger">*</span></label><textarea name="notes" class="field-input" rows="4" placeholder="Ceritakan kronologi kejadian (waktu, tempat, keadaan)...">{{ old('notes') }}</textarea></div>
                @error('notes')<span class="text-danger text-sm">{{ $message }}</span>@enderror
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('kasus-pelanggaran.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="file-plus" class="icon-sm"></i> Simpan Kasus</button>
                </div>
            </form>
        </div>

    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
    <script>
        function caseForm(){return{
            results:[],selectedStudentId:null,selectedStudentName:'',searchTimeout:null,
            groupId:@js($selectedGroupId),leafId:@js(old('violation_category_id')),meta:null,metaLoading:false,
            onGroupChange(){this.leafId='';this.meta=null;this.filterSubs();},
            filterSubs(){const sel=document.getElementById('leafSelect');if(!sel)return;let match=null;
                sel.querySelectorAll('option[data-group]').forEach(o=>{const ok=o.dataset.group===String(this.groupId??'');o.hidden=!ok;o.style.display=ok?'':'none';if(ok&&!match)match=o;});
                // Kategori induk tanpa anak = sub tunggal, langsung terpilih.
                if(match&&String(match.dataset.group)===String(this.groupId)&&!this.hasSiblingSubs()){this.leafId=match.value;this.onLeafChange();}},
            hasSiblingSubs(){const sel=document.getElementById('leafSelect');if(!sel)return false;let n=0;
                sel.querySelectorAll('option[data-group]').forEach(o=>{if(o.dataset.group===String(this.groupId??'')&&!o.hidden)n++});
                return n>1;},
            leafPoints(){const o=document.querySelector('#leafSelect option[value="'+this.leafId+'"]');return o?o.dataset.points:'-';},
            leafTiers(){if(this.meta&&Array.isArray(this.meta.tiers)&&this.meta.tiers.length)return this.meta.tiers.join(' / ');return this.leafPoints();},
            leafSeverity(){const o=document.querySelector('#leafSelect option[value="'+this.leafId+'"]');return o?o.dataset.severity:'-';},
            async onLeafChange(){this.meta=null;if(!this.leafId){return;}this.metaLoading=true;
                try{const r=await fetch('/violation-categories/'+this.leafId+'/rekomendasi');this.meta=r.ok?await r.json():null;}
                catch(e){this.meta=null}finally{this.metaLoading=false;this.$nextTick(()=>lucide.createIcons());}},
            searchStudent(q){clearTimeout(this.searchTimeout);if(q.length<2){this.results=[];return}this.searchTimeout=setTimeout(async()=>{try{const r=await fetch('/students/search?q='+encodeURIComponent(q));this.results=Array.isArray(r.data)?r.data:await r.json()||[]}catch(e){this.results=[]}},250)},
            selectStudent(s){this.selectedStudentId=s.id;this.selectedStudentName=s.full_name;this.results=[];document.querySelector('input[name=student_search]').value='';},
            clearStudent(){this.selectedStudentId=null;this.selectedStudentName='';},
            init(){this.$nextTick(()=>{this.filterSubs();if(this.leafId)this.onLeafChange();lucide.createIcons();})}
        }}
    </script>
</x-app-layout>
