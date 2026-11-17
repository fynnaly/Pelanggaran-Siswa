<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th style="width:48px"><x-sortable-header column="id" label="No" :sort="$sort" :order="$order" route="teachers.index" :params="request()->except('sort','order','page')" /></th>
                <th><x-sortable-header column="full_name" label="Nama" :sort="$sort" :order="$order" route="teachers.index" :params="request()->except('sort','order','page')" /></th>
                <th style="width:120px"><x-sortable-header column="gender" label="Jenis Kelamin" :sort="$sort" :order="$order" route="teachers.index" :params="request()->except('sort','order','page')" /></th>
                <th><x-sortable-header column="nip" label="NIP" :sort="$sort" :order="$order" route="teachers.index" :params="request()->except('sort','order','page')" /></th>
                <th><x-sortable-header column="mata_pelajaran" label="Mata Pelajaran" :sort="$sort" :order="$order" route="teachers.index" :params="request()->except('sort','order','page')" /></th>
                <th><x-sortable-header column="jabatan" label="Jabatan" :sort="$sort" :order="$order" route="teachers.index" :params="request()->except('sort','order','page')" /></th>
                <th>Akun Pengguna</th>
                <th><x-sortable-header column="status" label="Status" :sort="$sort" :order="$order" route="teachers.index" :params="request()->except('sort','order','page')" /></th>
                <th style="width:100px">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($teachers as $teacher)
                <tr>
                    <td class="text-sm text-muted">{{ $teacher->id }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;min-width:0">
                            <img src="{{ $teacher->avatar_url }}" alt="Foto {{ $teacher->full_name }}"
                                 style="width:34px;height:34px;border-radius:999px;object-fit:cover;flex-shrink:0;border:1px solid var(--border);background:var(--surface)">
                            <strong style="min-width:0">{{ $teacher->full_name }}</strong>
                        </div>
                    </td>
                    <td class="text-sm">{{ $teacher->gender === 'L' ? 'Laki-laki' : ($teacher->gender === 'P' ? 'Perempuan' : '-') }}</td>
                    <td class="mono">{{ $teacher->nip ?? '-' }}</td>
                    <td>{{ $teacher->mata_pelajaran ?? '-' }}</td>
                    <td class="text-sm">{{ $teacher->jabatan ?? '-' }}</td>
                    <td class="text-sm">
                        @if($teacher->user)
                            {{ $teacher->user->email }}
                        @else
                            <span class="text-muted">Belum tertaut</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $teacher->status === 'active' ? 'badge-success' : 'badge-neutral' }}">
                            {{ $teacher->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td>
                        <div class="action-cell">
                            @if(auth()->user()?->hasAnyRole(['admin','kesiswaan','kepala_sekolah']))
                                <a href="{{ route('teachers.edit', $teacher) }}" class="action-btn" title="Edit"><i data-lucide="pencil" style="width:14px;height:14px"></i></a>
                                <form action="{{ route('teachers.toggle-status', $teacher) }}" method="POST" class="inline" onsubmit="return confirm('{{ $teacher->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }} data guru {{ $teacher->full_name }}?')">
                                    @csrf
                                    @method('PATCH')
                                    <button class="action-btn" title="Toggle Status" aria-label="Toggle status"><i data-lucide="power" style="width:14px;height:14px"></i></button>
                                </form>
                            @else
                                <span class="text-sm text-muted">-</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="padding:40px;text-align:center;color:var(--on-surface-muted)">
                        <i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>
                        @if(request('q') || request('status'))
                            Tidak ada guru yang cocok.
                        @else
                            Belum ada data guru.
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex-wrap:wrap;margin-top:var(--sp-lg)">
    <span class="text-sm text-muted">Menampilkan {{ $teachers->firstItem() ?? 0 }}-{{ $teachers->lastItem() ?? 0 }} dari {{ $teachers->total() }}</span>
    <div class="pagination" style="margin-top:0">{{ $teachers->links() }}</div>
</div>
