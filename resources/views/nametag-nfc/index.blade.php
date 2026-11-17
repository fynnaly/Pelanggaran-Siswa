<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>
                    Data Nametag NFC Siswa
                </h1>
                <p class="text-muted text-sm">
                    <strong>{{ $assignedCount }}</strong> dari {{ $totalStudents }} siswa terpasang nametag NFC
                    @if($unassignedCount > 0)
                        &bull; <span class="text-amber-600 dark:text-amber-400 font-medium">{{ $unassignedCount }} siswa belum dipasangkan</span>
                    @endif
                </p>
            </div>
            @php $isManager = auth()->user()?->hasAnyRole(['admin','bk','kesiswaan','kepala_sekolah']); @endphp
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('nametag-nfc.template') }}" class="btn btn-secondary btn-sm" title="Download template format XLSX untuk import">
                    <i data-lucide="file-down" class="icon-sm"></i> Template
                </a>
                <a href="{{ route('nametag-nfc.export') }}" class="btn btn-secondary btn-sm" title="Export seluruh data nametag ke XLSX">
                    <i data-lucide="download" class="icon-sm"></i> Export
                </a>
                @if($isManager)
                <a href="{{ route('nametag-nfc.create') }}" class="btn btn-primary btn-sm">
                    <i data-lucide="plus" class="icon-sm"></i> Pasangkan Nametag
                </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="main-wrap">
        @if(session('success'))
            <div class="alert alert-success" style="margin-bottom:var(--sp-md)">
                <i data-lucide="check-circle" class="icon-sm"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-error" style="margin-bottom:var(--sp-md)">
                <i data-lucide="alert-circle" class="icon-sm"></i> {{ session('error') }}
            </div>
        @endif

        {{-- Filter & Import Toolbar --}}
        <div class="toolbar" style="margin-bottom:var(--sp-md)">
            <form action="{{ route('nametag-nfc.index') }}" method="GET" class="toolbar-form toolbar-form--filters">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari Kode UID / Nama Siswa / NIS..." class="input toolbar-search">

                <select name="status" class="input w-auto min-w-[130px] max-sm:w-full max-sm:min-w-0" onchange="this.form.submit()">
                    <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Semua Status</option>
                    <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>Sudah Terpasang ({{ $assignedCount }})</option>
                    <option value="unassigned" {{ request('status') === 'unassigned' ? 'selected' : '' }}>Belum Terpasang ({{ $unassignedCount }})</option>
                </select>

                <select name="class_id" class="input w-auto min-w-[130px] max-sm:w-full max-sm:min-w-0" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-secondary btn-sm max-sm:w-full max-sm:justify-center">
                    <i data-lucide="filter" class="icon-sm"></i> Saring
                </button>

                <a href="{{ route('nametag-nfc.index') }}" class="btn btn-secondary btn-sm max-sm:w-full max-sm:justify-center" title="Bersihkan semua filter">
                    <i data-lucide="x" class="icon-sm"></i> Reset
                </a>
            </form>

            @if($isManager)
                <form action="{{ route('nametag-nfc.import') }}" method="POST" enctype="multipart/form-data"
                      class="toolbar-form toolbar-form--import">
                    @csrf
                    <label class="text-sm text-muted shrink-0 whitespace-nowrap max-sm:w-full">Import File:</label>
                    <input type="file" name="file" accept=".xlsx,.csv,.txt" required class="input max-sm:w-full" style="padding:6px 10px">
                    <button type="submit" class="btn btn-primary btn-sm max-sm:w-full max-sm:justify-center" title="Unggah file XLSX/CSV pencocokan nametag">
                        <i data-lucide="upload" class="icon-sm"></i> Unggah
                    </button>
                </form>
            @endif
        </div>

        {{-- Tabel Data Nametag --}}
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:50px">No</th>
                        <th style="min-width:180px">Kode Unik / UID NFC</th>
                        <th style="min-width:240px">Siswa</th>
                        <th>Kelas</th>
                        <th>Status</th>
                        <th style="width:110px;text-align:center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $index => $student)
                        @php
                            $hasNfc = !empty($student->nfc_uid);
                            $zone = $student->zone;
                        @endphp
                        <tr>
                            <td class="text-sm text-muted">
                                {{ ($students->currentPage() - 1) * $students->perPage() + $index + 1 }}
                            </td>
                            <td>
                                @if($hasNfc)
                                    <div style="display:inline-flex;align-items:center;gap:6px">
                                        <i data-lucide="nfc" style="width:16px;height:16px;color:var(--primary)"></i>
                                        <span class="mono font-bold" style="font-size:0.92rem;letter-spacing:0.04em;background:var(--bs);color:var(--primary);padding:3px 8px;border-radius:6px;border:1px solid rgba(0,0,0,0.06)">
                                            {{ $student->nfc_uid }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-muted text-sm font-medium" style="display:inline-flex;align-items:center;gap:4px">
                                        <i data-lucide="slash" style="width:14px;height:14px"></i> Belum ada UID
                                    </span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('students.show', $student) }}" 
                                   class="font-bold hover:underline" 
                                   style="color:var(--primary);font-size:0.95rem;display:inline-flex;align-items:center;gap:5px" 
                                   title="Klik untuk membuka rincian profil siswa {{ $student->full_name }}">
                                    <span>{{ $student->full_name }}</span>
                                    <i data-lucide="external-link" style="width:13px;height:13px;opacity:0.7"></i>
                                </a>
                            </td>
                            <td class="text-sm font-medium" style="white-space:nowrap">
                                {{ $student->currentEnrollment?->schoolClass?->name ?? '-' }}
                            </td>
                            <td>
                                @if($hasNfc)
                                    <span class="badge badge-success" style="display:inline-flex;align-items:center;gap:4px">
                                        <i data-lucide="check" style="width:12px;height:12px"></i> Terpasang
                                    </span>
                                @else
                                    <span class="badge badge-neutral" style="display:inline-flex;align-items:center;gap:4px;color:var(--on-surface-muted)">
                                        <i data-lucide="clock" style="width:12px;height:12px"></i> Belum
                                    </span>
                                @endif
                            </td>
                            <td style="text-align:center">
                                @if($isManager)
                                <div class="action-cell" style="justify-content:center">
                                    <a href="{{ route('nametag-nfc.edit', $student) }}"
                                       class="action-btn"
                                       title="{{ $hasNfc ? 'Ganti / Edit Kode UID' : 'Pasangkan Kode UID' }}">
                                        <i data-lucide="{{ $hasNfc ? 'pencil' : 'link' }}" style="width:14px;height:14px"></i>
                                    </a>

                                    @if($hasNfc)
                                        <form action="{{ route('nametag-nfc.destroy', $student) }}" method="POST"
                                              onsubmit="return confirm('Lepaskan nametag NFC ({{ $student->nfc_uid }}) dari siswa {{ $student->full_name }}?');"
                                              style="display:inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn" title="Lepaskan Nametag" style="color:var(--danger)">
                                                <i data-lucide="unlink" style="width:14px;height:14px"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                @else
                                <span class="text-muted text-sm">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:40px;text-align:center;color:var(--on-surface-muted)">
                                <i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>
                                Tidak ada data nametag yang cocok dengan pencarian / filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginasi --}}
        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex-wrap:wrap;margin-top:var(--sp-lg)">
            <span class="text-sm text-muted">
                Menampilkan {{ $students->firstItem() ?? 0 }}-{{ $students->lastItem() ?? 0 }} dari {{ $students->total() }} siswa
            </span>
            <div class="pagination" style="margin-top:0">
                {{ $students->links() }}
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</x-app-layout>
