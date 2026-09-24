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
                <div class="field"><label class="field-label">Kategori Pelanggaran <span class="text-danger">*</span></label>
                    <select name="violation_category_id" class="field-input" required @change="$dispatch('violation-changed', { points: $event.target.selectedOptions[0]?.dataset.points || 0 })">
                        <option value="">Pilih kategori...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" data-points="{{ $cat->points }}" {{ old('violation_category_id')==$cat->id?'selected':'' }}>{{ $cat->name }} ({{ $cat->points }} poin)</option>
                        @endforeach
                    </select>
                    <span class="help-text">Pilih jenis pelanggaran yang dilakukan siswa</span>
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
                <div class="field"><label class="field-label">Keterangan / Kronologi</label><textarea name="notes" class="field-input" rows="4" placeholder="Ceritakan kronologi kejadian (waktu, tempat, keadaan)...">{{ old('notes') }}</textarea></div>
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('kasus-pelanggaran.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center"><i data-lucide="file-plus" class="icon-sm"></i> Simpan Kasus</button>
                </div>
            </form>
        </div>

        {{-- Rekomendasi Pemulihan Poin --}}
        @php
            $achievements = \App\Models\AchievementCategory::where('status', 'active')->orderByDesc('points')->get();
        @endphp
        <div class="card" style="margin-top:var(--sp-lg)">
            <h2 style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-lg)"><i data-lucide="rotate-ccw" class="icon"></i> Rekomendasi Pemulihan Poin</h2>
            <p class="text-sm text-muted" style="margin-bottom:var(--sp-md)">Setiap pelanggaran mengurangi poin siswa. Pilih level kesulitan untuk melihat pencapaian yang sesuai:</p>
            <div x-data="recoveryTabs()">
                <div class="recovery-tabs">
                    <label class="recovery-tab" :class="level==='ringan' && 'active-ringan'">
                        <input type="radio" name="recovery_level" value="ringan" x-model="level" style="display:none"> Ringan
                    </label>
                    <label class="recovery-tab" :class="level==='sedang' && 'active-sedang'">
                        <input type="radio" name="recovery_level" value="sedang" x-model="level" style="display:none"> Sedang
                    </label>
                    <label class="recovery-tab" :class="level==='besar' && 'active-besar'">
                        <input type="radio" name="recovery_level" value="besar" x-model="level" style="display:none"> Besar
                    </label>
                </div>
                @if($achievements->count())
                    <div class="recovery-cards">
                        @foreach($achievements as $ach)
                            @php
                                $achLevel = $ach->points <= 5 ? 'ringan' : ($ach->points <= 15 ? 'sedang' : 'besar');
                            @endphp
                            <div x-show="level === '{{ $achLevel }}'"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 transform -translate-y-1"
                                 x-transition:enter-end="opacity-100 transform translate-y-0"
                                 class="recovery-card">
                                <div class="recovery-card-icon"><i data-lucide="trophy" style="width:18px;height:18px"></i></div>
                                <div class="recovery-card-body">
                                    <div class="recovery-card-title">{{ $ach->name }}</div>
                                    <div class="text-sm text-muted">{{ $ach->code }} &middot; +{{ $ach->points }} poin per pencapaian</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="recovery-empty">
                        <p class="text-sm text-muted">Belum ada kategori pencapaian aktif.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
    <script>
        function caseForm(){return{results:[],selectedStudentId:null,selectedStudentName:'',searchTimeout:null,searchStudent(q){clearTimeout(this.searchTimeout);if(q.length<2){this.results=[];return}this.searchTimeout=setTimeout(async()=>{try{const r=await fetch('/students/search?q='+encodeURIComponent(q));this.results=Array.isArray(r.data)?r.data:await r.json()||[]}catch(e){this.results=[]}},250)},selectStudent(s){this.selectedStudentId=s.id;this.selectedStudentName=s.full_name;this.results=[];document.querySelector('input[name=student_search]').value='';},clearStudent(){this.selectedStudentId=null;this.selectedStudentName='';}}}
        function recoveryTabs(){return{level:'ringan',init(){this.$el.parentElement.addEventListener('violation-changed',(e)=>{const pts=parseInt(e.detail.points)||0;if(pts>=20)this.level='besar';else if(pts>=8)this.level='sedang';else this.level='ringan'})}}}
    </script>
</x-app-layout>
