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

        {{-- Filter: tipe (Guru/Siswa) + jabatan (hanya saat Guru) + pencarian --}}
        <div class="toolbar" style="display:flex;flex-direction:column;gap:var(--sp-sm);align-items:stretch;margin-bottom:var(--sp-md)">
            <form action="{{ route('users.index') }}" method="GET" data-live="#userRegion" id="userFilter"
                  style="display:flex;flex-wrap:wrap;gap:var(--sp-sm);align-items:flex-end;width:100%">
                <div style="display:flex;flex-direction:column;gap:4px;flex:1 1 170px;min-width:170px">
                    <label style="font-size:.75rem;font-weight:700;color:var(--on-surface-muted);text-transform:uppercase;letter-spacing:.05em">Tipe Pengguna</label>
                    <select name="type" id="userType" class="input">
                        <option value="">Semua Pengguna</option>
                        <option value="guru" {{ ($type ?? '')==='guru'?'selected':'' }}>Guru / Staff</option>
                        <option value="siswa" {{ ($type ?? '')==='siswa'?'selected':'' }}>Siswa</option>
                    </select>
                </div>
                <div id="jabatanField" style="display:flex;flex-direction:column;gap:4px;flex:1 1 160px;min-width:160px;{{ ($type ?? '')==='guru' ? '' : 'display:none' }}">
                    <label style="font-size:.75rem;font-weight:700;color:var(--on-surface-muted);text-transform:uppercase;letter-spacing:.05em">Jabatan</label>
                    <select name="jabatan" id="jabatanSelect" class="input" {{ ($type ?? '')==='guru' ? '' : 'disabled' }}>
                        <option value="">Semua Jabatan</option>
                        @foreach(['guru','bk','kesiswaan','kepala_sekolah','admin'] as $j)
                            <option value="{{ $j }}" {{ ($jabatan ?? '')===$j?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$j)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;flex-direction:column;gap:4px;flex:2 1 240px;min-width:220px">
                    <label style="font-size:.75rem;font-weight:700;color:var(--on-surface-muted);text-transform:uppercase;letter-spacing:.05em">Cari</label>
                    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Nama, email, NISN, NIS, NIP, atau mapel..." class="input">
                </div>
                <div style="display:flex;flex-direction:column;gap:4px;flex:1 1 200px;min-width:200px">
                    <label style="font-size:.75rem;font-weight:700;color:var(--on-surface-muted);text-transform:uppercase;letter-spacing:.05em">Permintaan Akun</label>
                    <select name="deletion_requested" class="input">
                        <option value="" {{ ! $deletionRequested ? 'selected' : '' }}>Semua akun</option>
                        <option value="1" {{ $deletionRequested ? 'selected' : '' }}>Menunggu persetujuan</option>
                    </select>
                </div>
            </form>
            <form action="{{ route('users.import') }}" method="POST" enctype="multipart/form-data"
                  style="display:flex;align-items:center;gap:var(--sp-sm);flex-wrap:wrap;width:100%;max-width:560px">
                @csrf
                <label class="text-sm text-muted" style="white-space:nowrap">Import CSV:</label>
                <input type="file" name="file" accept=".csv,.txt,.xlsx" required class="input" style="width:auto;padding:6px 10px;flex:1;min-width:0">
                <button type="submit" class="btn btn-primary btn-sm" style="flex-shrink:0"><i data-lucide="upload" class="icon-sm"></i> Unggah</button>
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
                        <th>Role / Jabatan</th>
                        <th>Status Akun</th>
                        <th>Identitas</th>
                        <th>Permintaan Hapus</th>
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
                                    <a href="{{ route('students.show', $user->student) }}" style="font-weight:600">{{ $user->student->nisn }}</a>
                                    <span class="text-muted"> &middot; {{ $user->student->full_name }}</span>
                                    <span class="text-muted"> &middot; {{ $user->student->currentEnrollment?->schoolClass?->name ?? '-' }}</span>
                                @elseif($user->teacher)
                                    <span style="font-weight:600">{{ $user->teacher->nip ?: 'Tanpa NIP' }}</span>
                                    <span class="text-muted"> &middot; {{ $user->teacher->mata_pelajaran ?: 'Mapel belum diisi' }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($user->deletion_requested_at)
                                    <span class="badge badge-warning">Menunggu persetujuan</span>
                                    <div class="text-xs text-muted" style="margin-top:4px">
                                        {{ $user->deletion_requested_at->copy()->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i') }} WIB
                                    </div>
                                    @if($user->is(auth()->user()))
                                        <span class="text-xs text-muted">Perlu diproses pengelola lain.</span>
                                    @endif
                                @else
                                    <span class="text-sm text-muted">-</span>
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
                            <td colspan="8" style="padding:40px;text-align:center;color:var(--on-surface-muted)">
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
            var jabatanField = document.getElementById('jabatanField');
            var jabatanSelect = document.getElementById('jabatanSelect');
            if (!typeSelect || !jabatanField || !jabatanSelect) return;

            function syncJabatan() {
                var isStaff = typeSelect.value === 'guru';
                jabatanField.style.display = isStaff ? 'flex' : 'none';
                jabatanSelect.disabled = !isStaff;
                if (!isStaff) jabatanSelect.value = '';
            }

            typeSelect.addEventListener('change', syncJabatan);
            syncJabatan();
        })();
    </script>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
