<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Pengguna</h1>
                <p class="text-muted text-sm" data-live-count>{{ $users->total() }} akun terdaftar</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
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
        <div class="toolbar" style="margin-bottom:var(--sp-md);display:flex;gap:var(--sp-sm);flex-wrap:wrap;align-items:flex-end">
            <form action="{{ route('users.index') }}" method="GET" data-live="#userRegion" id="userFilter" style="display:flex;gap:var(--sp-sm);flex:1;flex-wrap:wrap;align-items:flex-end">
                <div style="display:flex;flex-direction:column;gap:4px">
                    <label style="font-size:.75rem;font-weight:700;color:var(--on-surface-muted);text-transform:uppercase;letter-spacing:.05em">Tipe Pengguna</label>
                    <select name="type" class="input" style="min-width:160px" onchange="this.form.submit()">
                        <option value="">Semua Pengguna</option>
                        <option value="guru" {{ ($type ?? '')==='guru'?'selected':'' }}>Guru / Staff</option>
                        <option value="siswa" {{ ($type ?? '')==='siswa'?'selected':'' }}>Siswa</option>
                    </select>
                </div>
                @if(($type ?? '')==='guru')
                <div style="display:flex;flex-direction:column;gap:4px">
                    <label style="font-size:.75rem;font-weight:700;color:var(--on-surface-muted);text-transform:uppercase;letter-spacing:.05em">Jabatan</label>
                    <select name="jabatan" class="input" style="min-width:150px" onchange="this.form.submit()">
                        <option value="">Semua Jabatan</option>
                        @foreach(['guru','bk','kesiswaan','kepala_sekolah','admin'] as $j)
                            <option value="{{ $j }}" {{ ($jabatan ?? '')===$j?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$j)) }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div style="display:flex;flex-direction:column;gap:4px;flex:1;min-width:200px">
                    <label style="font-size:.75rem;font-weight:700;color:var(--on-surface-muted);text-transform:uppercase;letter-spacing:.05em">Cari</label>
                    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Nama, email, NISN, atau NIS..." class="input">
                </div>
            </form>
            <div style="display:flex;align-items:center;gap:var(--sp-sm);flex-wrap:wrap">
                <form action="{{ route('users.import') }}" method="POST" enctype="multipart/form-data" style="display:flex;align-items:center;gap:var(--sp-sm);flex:1;min-width:0">
                    @csrf
                    <label class="text-sm text-muted" style="white-space:nowrap">Import CSV:</label>
                    <input type="file" name="file" accept=".csv,.txt" required class="input" style="width:auto;padding:6px 10px;flex:1;min-width:0">
                    <button type="submit" class="btn btn-primary btn-sm" style="flex-shrink:0"><i data-lucide="upload" class="icon-sm"></i> Unggah</button>
                </form>
            </div>
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
                        <th>Identitas</th>
                        <th style="width:100px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="text-sm text-muted">{{ $user->id }}</td>
                            <td style="font-weight:600">{{ $user->name }}</td>
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
                            <td class="text-sm">
                                @if($user->student)
                                    <a href="{{ route('students.show', $user->student) }}" style="font-weight:600">{{ $user->student->nisn }}</a>
                                    <span class="text-muted"> &middot; {{ $user->student->full_name }}</span>
                                    <span class="text-muted"> &middot; {{ $user->student->currentEnrollment?->schoolClass?->name ?? '-' }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-cell">
                                    <a href="{{ route('users.show', $user) }}" class="action-btn" title="Detail"><i data-lucide="eye" style="width:14px;height:14px"></i></a>
                                    <a href="{{ route('users.edit', $user) }}" class="action-btn" title="Edit"><i data-lucide="pencil" style="width:14px;height:14px"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:40px;text-align:center;color:var(--on-surface-muted)">
                                <i data-lucide="inbox" style="width:40px;height:40px;display:block;margin:0 auto var(--sp-sm);opacity:.4"></i>
                                Belum ada pengguna.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="display:flex;justify-content:center;margin-top:var(--sp-lg)">
            <div class="pagination" style="margin-top:0">{{ $users->links() }}</div>
        </div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
