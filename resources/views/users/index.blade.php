<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Pengguna</h1>
                <p class="text-muted text-sm" data-live-count>{{ $users->total() }} akun terdaftar</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('users.index', ['deletion_requested' => '1']) }}" class="btn btn-secondary btn-sm">
                    Permintaan hapus ({{ $pendingDeletionCount }})
                </a>
                <a href="{{ route('users.template') }}" class="btn btn-secondary btn-sm"><i data-lucide="file-down" class="icon-sm"></i> Template</a>
                <a href="{{ route('users.export') }}" class="btn btn-secondary btn-sm"><i data-lucide="download" class="icon-sm"></i> Export</a>
                @if(auth()->user()?->hasAnyRole(['admin','kepala_sekolah','kesiswaan']))
                    <a href="{{ route('users.create') }}" class="btn btn-primary">
                        <i data-lucide="user-plus" style="width:16px;height:16px"></i> Tambah
                    </a>
                @endif
            </div>
        </div>
    </x-slot>
    <div class="main-wrap">
        @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}</div>@endif

        @php $generatedAccounts = session('generated_accounts', session('generated_credentials', [])); @endphp
        @if(! empty($generatedAccounts))
            <div class="card" style="margin-bottom:var(--sp-lg)">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:var(--sp-sm);flex-wrap:wrap">
                    <div>
                        <h2 style="font-size:1.05rem;font-weight:700">Kredensial Akun ({{ count($generatedAccounts) }})</h2>
                        <p class="text-sm text-muted" style="margin-top:4px">Daftar ini tersimpan di sesi browser sampai Anda membuat daftar baru, menghapusnya, atau logout. Unduh CSV agar aman.</p>
                    </div>
                    <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                        <a href="{{ route('users.generate.download') }}" class="btn btn-secondary btn-sm"><i data-lucide="download" class="icon-sm"></i> Unduh CSV</a>
                        <form method="POST" action="{{ route('users.generate.clear') }}" onsubmit="return confirm('Hapus daftar kredensial dari sesi? Password tidak bisa ditampilkan lagi.')">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="trash-2" class="icon-sm"></i> Hapus daftar</button>
                        </form>
                    </div>
                </div>
                <div class="table-wrap" style="margin-top:var(--sp-md);max-height:360px;overflow:auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Email</th>
                                <th>Password</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($generatedAccounts as $account)
                                <tr>
                                    <td style="font-weight:600">{{ $account['name'] }}</td>
                                    <td class="text-sm">{{ $account['class_name'] ?? '' }}</td>
                                    <td class="text-sm">{{ $account['email'] }}</td>
                                    <td class="text-sm" style="font-family:monospace;font-weight:700">{{ $account['password'] }}</td>
                                    <td><span class="badge">{{ $account['role'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="card" style="margin-bottom:var(--sp-lg);display:flex;justify-content:space-between;align-items:center;gap:var(--sp-md);flex-wrap:wrap">
            <div style="min-width:0">
                <h2 style="font-size:1.05rem;font-weight:700">Generate Akun Otomatis</h2>
                <p class="text-sm text-muted" style="margin-top:4px">
                    {{ $studentsWithoutAccounts }} siswa dan {{ $teachersWithoutAccounts }} guru aktif belum punya akun.
                    Email dibuat dari NIS/NIP, password dibuat 6 angka dan hanya ditampilkan sekali.
                </p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <form method="POST" action="{{ route('users.generate') }}" onsubmit="return confirm('Buat akun untuk semua siswa aktif yang belum punya akun?')">
                    @csrf
                    <input type="hidden" name="type" value="siswa">
                    <button type="submit" class="btn btn-primary btn-sm" {{ $studentsWithoutAccounts === 0 ? 'disabled' : '' }}>
                        <i data-lucide="user-plus" class="icon-sm"></i> Generate Akun Siswa ({{ $studentsWithoutAccounts }})
                    </button>
                </form>
                <form method="POST" action="{{ route('users.generate') }}" onsubmit="return confirm('Buat akun untuk semua guru aktif yang belum punya akun?')">
                    @csrf
                    <input type="hidden" name="type" value="guru">
                    <button type="submit" class="btn btn-primary btn-sm" {{ $teachersWithoutAccounts === 0 ? 'disabled' : '' }}>
                        <i data-lucide="user-plus" class="icon-sm"></i> Generate Akun Guru ({{ $teachersWithoutAccounts }})
                    </button>
                </form>
            </div>
        </div>

        {{-- Reset password massal: semua / per kelas / per tingkatan --}}
        <div class="card" style="margin-bottom:var(--sp-lg)">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:var(--sp-md);flex-wrap:wrap">
                <div style="min-width:0">
                    <h2 style="font-size:1.05rem;font-weight:700">Reset Password</h2>
                    <p class="text-sm text-muted" style="margin-top:4px">
                        Password baru dibuat 6 angka dan dapat diunduh selama sesi ini. Password lama langsung tidak berlaku.
                        Pilih <strong>Siswa/Guru/Semua</strong> untuk menyaring daftar di bawah sekaligus cakupan reset.
                        Pada <strong>Semua pengguna</strong>, akun Anda sendiri tidak ikut direset.
                    </p>
                </div>
                <form method="POST" action="{{ route('users.passwords.reset') }}"
                      style="display:flex;gap:var(--sp-sm);align-items:center;flex-wrap:wrap"
                      onsubmit="return confirm('Reset password akun yang dipilih? Password lama akan langsung tidak berlaku.')">
                    @csrf
                    <select name="type" id="resetType" class="input" style="width:auto;min-width:110px"
                            title="Jenis akun yang direset — daftar pengguna di bawah ikut tersaring" aria-label="Jenis akun yang direset">
                        <option value="siswa" {{ in_array($type ?? '', ['guru', 'all'], true) ? '' : 'selected' }}>Siswa</option>
                        <option value="guru" {{ ($type ?? '') === 'guru' ? 'selected' : '' }}>Guru</option>
                        <option value="all" {{ ($type ?? '') === 'all' ? 'selected' : '' }}>Semua pengguna</option>
                    </select>
                    <select name="scope" id="resetScope" class="input" style="width:auto;min-width:140px">
                        <option value="all">Semua akun</option>
                        <option value="class">Per kelas</option>
                        <option value="grade">Per tingkatan</option>
                    </select>
                    <select name="class_id" id="resetClassField" class="input" style="width:auto;min-width:160px;display:none" disabled>
                        <option value="">Pilih kelas...</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}{{ $class->academicYear?->name ? ' · '.$class->academicYear->name : '' }}</option>
                        @endforeach
                    </select>
                    <select name="grade_level" id="resetGradeField" class="input" style="width:auto;min-width:150px;display:none" disabled>
                        <option value="">Pilih tingkatan...</option>
                        @foreach($gradeOptions as $grade)
                            <option value="{{ $grade }}">{{ $grade }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="key-round" class="icon-sm"></i> Reset Password</button>
                </form>
            </div>
        </div>

        {{-- Toolbar: pencarian + filter tipe/jabatan/permintaan akun + import --}}
        <div class="toolbar">
            <form action="{{ route('users.index') }}" method="GET" data-live="#userRegion" id="userFilter"
                  class="toolbar-form toolbar-form--filters">
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cari nama, email, NISN, NIS, NIP, atau mapel..." class="input toolbar-search">
                <select name="type" id="userType" class="input w-auto min-w-[130px] max-sm:w-full max-sm:min-w-0">
                    <option value="">Semua Pengguna</option>
                    <option value="guru" {{ ($type ?? '')==='guru'?'selected':'' }}>Guru / Staff</option>
                    <option value="siswa" {{ ($type ?? '')==='siswa'?'selected':'' }}>Siswa</option>
                </select>
                <select name="jabatan" id="jabatanSelect" class="input w-auto min-w-[120px] max-sm:w-full max-sm:min-w-0" style="{{ ($type ?? '')==='guru' ? '' : 'display:none' }}" {{ ($type ?? '')==='guru' ? '' : 'disabled' }}>
                    <option value="">Semua Jabatan</option>
                    @foreach(['guru','bk','kesiswaan','kepala_sekolah','admin'] as $j)
                        <option value="{{ $j }}" {{ ($jabatan ?? '')===$j?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$j)) }}</option>
                    @endforeach
                </select>
                <select name="deletion_requested" class="input w-auto min-w-[150px] max-sm:w-full max-sm:min-w-0">
                    <option value="" {{ ! $deletionRequested ? 'selected' : '' }}>Semua akun</option>
                    <option value="1" {{ $deletionRequested ? 'selected' : '' }}>Menunggu persetujuan</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm max-sm:w-full max-sm:justify-center">
                    <i data-lucide="filter" class="icon-sm"></i> Saring
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm max-sm:w-full max-sm:justify-center" title="Bersihkan semua filter">
                    <i data-lucide="x" class="icon-sm"></i> Reset
                </a>
            </form>
            <form action="{{ route('users.import') }}" method="POST" enctype="multipart/form-data"
                  class="toolbar-form toolbar-form--import">
                @csrf
                <label class="text-sm text-muted shrink-0 whitespace-nowrap max-sm:w-full">Import CSV:</label>
                <input type="file" name="file" accept=".csv,.txt,.xlsx" required class="input max-sm:w-full" style="padding:6px 10px">
                <button type="submit" class="btn btn-primary btn-sm max-sm:w-full max-sm:justify-center"><i data-lucide="upload" class="icon-sm"></i> Unggah</button>
            </form>
        </div>

        <div id="userRegion">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:48px"><x-sortable-header column="id" label="No" :sort="$sort" :order="$order" route="users.index" :params="request()->except('sort','order','page')" /></th>
                        <th><x-sortable-header column="name" label="Nama" :sort="$sort" :order="$order" route="users.index" :params="request()->except('sort','order','page')" /></th>
                        <th><x-sortable-header column="email" label="Email" :sort="$sort" :order="$order" route="users.index" :params="request()->except('sort','order','page')" /></th>
                        <th><x-sortable-header column="role" label="Role / Jabatan" :sort="$sort" :order="$order" route="users.index" :params="request()->except('sort','order','page')" /></th>
                        <th><x-sortable-header column="is_active" label="Status Akun" :sort="$sort" :order="$order" route="users.index" :params="request()->except('sort','order','page')" /></th>
                        <th>Identitas</th>
                        <th style="width:100px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="text-sm text-muted">{{ $user->id }}</td>
                            <td style="font-weight:600">
                                <div style="display:flex;align-items:center;gap:10px;min-width:0">
                                    <img src="{{ $user->avatar_url }}" alt="Foto {{ $user->name }}"
                                         style="width:30px;height:30px;border-radius:999px;object-fit:cover;flex-shrink:0;border:1px solid var(--border);background:var(--surface)">
                                    <span style="min-width:0">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="text-sm text-muted">{{ $user->email }}</td>
                            <td>
                                <div style="display:flex;gap:4px;flex-wrap:wrap">
                                    @forelse($user->roles as $r)
                                        <span class="badge" style="
                                            @if($r->name==='siswa') background:var(--bs);color:var(--bt)
                                            @elseif(in_array($r->name,['admin','kepala_sekolah'])) background:var(--ds);color:var(--dt)
                                            @else background:var(--ws);color:var(--wt) @endif
                                        ">{{ $r->name }}</span>
                                    @empty
                                        <span class="text-sm text-muted">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                @if($user->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-neutral">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-sm">
                                @if($user->student)
                                    <a href="{{ route('students.show', $user->student) }}" style="font-weight:600">{{ $user->student->full_name }}</a>
                                    <span class="text-muted" style="margin-left:6px">{{ $user->student->nisn }}</span>
                                    <span class="text-muted" style="margin-left:6px">{{ $user->student->currentEnrollment?->schoolClass?->name ?? '-' }}</span>
                                @elseif($user->teacher)
                                    <span style="font-weight:600">{{ $user->teacher->nip ?: 'Tanpa NIP' }}</span>
                                    <span class="text-muted" style="margin-left:6px">{{ $user->teacher->mata_pelajaran ?: 'Mapel belum diisi' }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-cell">
                                    @if($user->deletion_requested_at && ! $user->is(auth()->user()))
                                        <form action="{{ route('users.deletion-requests.approve', $user) }}" method="POST" class="inline" onsubmit="return confirm('Setujui dan nonaktifkan akun ini?')">
                                            @csrf
                                            @method('PATCH')
                                            <button class="action-btn" title="Setujui penghapusan" style="color:var(--success)"><i data-lucide="check" style="width:14px;height:14px"></i></button>
                                        </form>
                                        <form action="{{ route('users.deletion-requests.reject', $user) }}" method="POST" class="inline" onsubmit="return confirm('Tolak permintaan penghapusan akun ini?')">
                                            @csrf
                                            @method('PATCH')
                                            <button class="action-btn" title="Tolak penghapusan" style="color:var(--danger)"><i data-lucide="x" style="width:14px;height:14px"></i></button>
                                        </form>
                                    @endif
                                    @if(! $user->is_active)
                                        <form action="{{ route('users.activate', $user) }}" method="POST" class="inline" onsubmit="return confirm('Aktifkan kembali akun ini?')">
                                            @csrf
                                            @method('PATCH')
                                            <button class="action-btn" title="Aktifkan akun" aria-label="Aktifkan akun"><i data-lucide="user-check" style="width:14px;height:14px"></i></button>
                                        </form>
                                    @endif
                                    <a href="{{ route('users.show', $user) }}" class="action-btn" title="Detail"><i data-lucide="eye" style="width:14px;height:14px"></i></a>
                                    <a href="{{ route('users.edit', $user) }}" class="action-btn" title="Edit"><i data-lucide="pencil" style="width:14px;height:14px"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:40px;text-align:center;color:var(--on-surface-muted)">
                                <i data-lucide="inbox" style="width:40px;height:40px;display:block;margin:0 auto var(--sp-sm);opacity:.4"></i>
                                {{ $deletionRequested ? 'Belum ada permintaan penghapusan yang menunggu.' : 'Belum ada pengguna.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex-wrap:wrap;margin-top:var(--sp-lg)">
            <span class="text-sm text-muted">Menampilkan {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }}</span>
            <div class="pagination" style="margin-top:0">{{ $users->links() }}</div>
        </div>
        </div>
    </div>
    <script>
        // Tampilkan filter Jabatan hanya saat tipe = Guru / Staff.
        // Jabatan yang tersembunyi di-disabled agar tidak ikut terkirim sebagai query.
        (function () {
            var typeSelect = document.getElementById('userType');
            var jabatanSelect = document.getElementById('jabatanSelect');
            if (!typeSelect || !jabatanSelect) return;

            function syncJabatan() {
                var isStaff = typeSelect.value === 'guru';
                jabatanSelect.style.display = isStaff ? '' : 'none';
                jabatanSelect.disabled = !isStaff;
                if (!isStaff) jabatanSelect.value = '';
            }

            typeSelect.addEventListener('change', syncJabatan);
            syncJabatan();
        })();

        // Tampilkan pilihan kelas/tingkatan sesuai cakupan reset password.
        // Pilihan Siswa/Guru di kartu ini juga menyaring daftar pengguna, dan
        // filter daftar ikut memindahkan pilihan reset — dua arah.
        (function () {
            var type = document.getElementById('resetType');
            var listType = document.getElementById('userType');
            var scope = document.getElementById('resetScope');
            var classField = document.getElementById('resetClassField');
            var gradeField = document.getElementById('resetGradeField');
            if (!scope || !classField || !gradeField) return;

            function sync() {
                var isClass = scope.value === 'class';
                var isGrade = scope.value === 'grade';
                classField.style.display = isClass ? '' : 'none';
                classField.disabled = !isClass;
                classField.required = isClass;
                if (!isClass) classField.value = '';
                gradeField.style.display = isGrade ? '' : 'none';
                gradeField.disabled = !isGrade;
                gradeField.required = isGrade;
                if (!isGrade) gradeField.value = '';
            }

            // Kelas/tingkatan hanya berlaku untuk akun siswa. Reset guru dan
            // "semua pengguna" selalu mencakup semua akun.
            function syncType() {
                var isSiswa = type && type.value === 'siswa';
                Array.prototype.forEach.call(scope.options, function (option) {
                    if (option.value === 'all') return;
                    option.disabled = !isSiswa;
                    option.hidden = !isSiswa;
                });
                if (!isSiswa && scope.value !== 'all') scope.value = 'all';
                sync();
            }

            // Filter daftar → pilihan reset mengikuti (semua → "Semua pengguna").
            if (listType) {
                listType.addEventListener('change', function () {
                    if (!type) return;
                    var value = listType.value;
                    type.value = (value === 'guru' || value === 'siswa') ? value : 'all';
                    syncType();
                });
            }

            // Pilihan reset → daftar ikut tersaring lewat form filter (data-live).
            if (type) {
                type.addEventListener('change', function () {
                    if (!listType) return;
                    listType.value = (type.value === 'guru' || type.value === 'siswa') ? type.value : '';
                    listType.dispatchEvent(new Event('change', { bubbles: true }));
                });
                type.addEventListener('change', syncType);
            }

            scope.addEventListener('change', sync);
            syncType();
        })();
    </script>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
