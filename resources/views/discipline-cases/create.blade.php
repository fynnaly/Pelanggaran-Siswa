<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Kasus / Tambah</div>
        <div class="page-header"><h1>Buat Kasus Baru</h1></div>
    </x-slot>
    <div class="main-wrap">
        <div class="card" style="max-width:600px">
            @if($errors->any())<div class="alert alert-error" style="margin-bottom:var(--sp-lg)">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
            <form action="{{ route('discipline-cases.store') }}" method="POST">
                @csrf
                <div class="field" x-data="studentAutocomplete()">
                    <label class="field-label">Siswa <span class="text-danger">*</span></label>
                    <input type="text" x-model="searchQuery" name="student_name" autocomplete="off" required class="field-input" placeholder="Cari nama siswa atau NISN..." @focus="showDropdown = true" @click.outside="showDropdown = false">
                    <input type="hidden" name="student_id" :value="selectedStudent ? selectedStudent.id : ''">
                    <div x-show="showDropdown && suggestions.length > 0 && !selectedStudent" x-transition class="autocomplete-dropdown">
                        <template x-for="s in suggestions" :key="s.id">
                            <div class="autocomplete-item" @click="selectStudent(s)">
                                <span style="font-weight:600" x-text="s.full_name"></span>
                                <span class="text-muted text-sm" x-text="s.nis + ' - ' + s.kelas"></span>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="field">
                    <label class="field-label">Kategori Pelanggaran <span class="text-danger">*</span></label>
                    <select name="violation_category_id" required class="field-input">
                        <option value="">-- Pilih kategori --</option>
                        @foreach($categories as $id => $name)<option value="{{ $id }}" {{ old('violation_category_id')==$id?'selected':'' }}>{{ $name }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="field-label">Deskripsi <span class="text-danger">*</span></label>
                    <textarea name="description" required rows="4" class="field-input" placeholder="Jelaskan kronologi pelanggaran...">{{ old('description') }}</textarea>
                </div>
                <input type="hidden" name="report_by" value="{{ auth()->id() }}">
                <input type="hidden" name="status" value="found">
                <div style="display:flex;gap:var(--sp-sm);margin-top:var(--sp-lg)">
                    <a href="{{ route('discipline-cases.index') }}" class="btn btn-secondary" style="flex:1;justify-content:center">Batal</a>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">Catat Kasus</button>
                </div>
            </form>
        </div>
    </div>
    <style>
        .autocomplete-dropdown{position:absolute;z-index:50;width:100%;margin-top:4px;background:var(--neutral);border:1px solid var(--border);border-radius:var(--r-sm);box-shadow:var(--sh-md);max-height:240px;overflow-y:auto}
        .autocomplete-item{padding:10px 14px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;transition:background .15s}
        .autocomplete-item:hover{background:var(--surface)}
    </style>
    <script>
        function studentAutocomplete(){return{searchQuery:'',suggestions:[],selectedStudent:@json($selectedStudent),showDropdown:false,debounceTimer:null,init(){this.searchQuery=this.selectedStudent?this.selectedStudent.full_name:'';this.$watch('searchQuery',(v)=>{if(this.selectedStudent&&v===this.selectedStudent.full_name)return;this.selectedStudent=null;if(this.debounceTimer)clearTimeout(this.debounceTimer);this.debounceTimer=setTimeout(()=>this.fetchSuggestions(v),250)})},fetchSuggestions(q){if(q.length<2){this.suggestions=[];return}fetch(`{{route('students.search')}}?q=${encodeURIComponent(q)}`).then(r=>r.json()).then(d=>{this.suggestions=d;this.showDropdown=d.length>0})},selectStudent(s){this.selectedStudent=s;this.searchQuery=s.full_name;this.suggestions=[];this.showDropdown=false}}}
    </script>
</x-app-layout>