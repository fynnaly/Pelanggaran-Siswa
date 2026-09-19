<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Daftar Siswa</div>
        <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-sm)">
            <div>
                <h1>Daftar Siswa</h1>
                <p class="text-muted text-sm">{{ $students->total() }} siswa terdaftar</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('students.template') }}" class="btn btn-secondary btn-sm">Template</a>
                <a href="{{ route('students.export') }}" class="btn btn-secondary btn-sm">Export</a>
                <a href="{{ route('students.create') }}" class="btn btn-primary">Tambah Siswa</a>
            </div>
        </div>
    </x-slot>

    <div class="main-wrap">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @if($classId)
            <div style="display:flex;align-items:center;gap:var(--sp-sm);margin-bottom:var(--sp-md)">
                <span class="badge" style="background:var(--bs);color:var(--bt)">Filter: Kelas {{ $students->first()?->schoolClass?->name ?? '#' . $classId }}</span>
                <a href="{{ route('students.index') }}" class="text-sm text-muted" style="color:var(--on-surface-muted)">Hapus filter</a>
            </div>
        @endif

        <div class="toolbar">
            <form action="{{ route('students.index') }}" method="GET" style="display:flex;gap:var(--sp-sm);flex:1;flex-wrap:wrap">
                @if($classId)
                    <input type="hidden" name="class_id" value="{{ $classId }}">
                @endif
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / NISN / NIS..." class="input" style="max-width:280px">
                <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
            </form>
            <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data" style="display:flex;align-items:center;gap:var(--sp-sm)">
                @csrf
                <label class="text-sm text-muted" style="white-space:nowrap">Import CSV:</label>
                <input type="file" name="file" accept=".csv,.txt" required class="input" style="width:auto;padding:6px 10px">
                <button type="submit" class="btn btn-primary btn-sm">Unggah</button>
            </form>
        </div>

        <form action="{{ route('classes.promote') }}" method="POST" id="bulkPromoteForm" style="display:none;margin-bottom:var(--sp-md)">
            @csrf
            <div class="card" style="display:flex;align-items:center;gap:var(--sp-md);flex-wrap:wrap">
                <span class="text-sm" style="font-weight:600" id="selectedCount">0</span>
                <span class="text-sm text-muted">siswa dipilih</span>
                <span style="color:var(--border)">|</span>
                <label class="text-sm" style="font-weight:600">Pindah ke:</label>
                <select name="class_id" required class="input" style="width:auto;min-width:150px">
                    <option value="">Pilih kelas...</option>
                    @foreach($classesForPromote as $id => $name)
                        @if($classId && $id == $classId) @continue @endif
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Naik Kelas</button>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:40px"><input type="checkbox" id="selectAll" class="checkbox"></th>
                        <th>NISN</th>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Status</th>
                        <th>Poin</th>
                        <th style="width:100px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php
                            $poin = $student->pointLedgers->first()?->balance_after ?? 2000;
                        @endphp
                        <tr>
                            <td><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-cb checkbox"></td>
                            <td class="mono">{{ $student->nisn }}</td>
                            <td class="mono">{{ $student->nis }}</td>
                            <td><strong>{{ $student->full_name }}</strong></td>
                            <td class="text-sm">{{ $student->schoolClass?->name ?? '-' }}</td>
                            <td>
                                @if($student->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif($student->status === 'graduated')
                                    <span class="badge" style="background:var(--bs);color:var(--bt)">Graduated</span>
                                @else
                                    <span class="badge" style="background:var(--surface);color:var(--on-surface-muted)">{{ ucfirst($student->status) }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="mono" style="font-weight:700;{{ $poin <= 500 ? 'color:var(--danger)' : ($poin <= 1000 ? 'color:var(--accent)' : 'color:var(--tertiary)') }}">{{ $poin }}</span>
                            </td>
                            <td>
                                <div class="action-cell">
                                    <a href="{{ route('students.edit', $student) }}" class="action-btn" title="Edit">E</a>
                                    <form action="{{ route('students.destroy', $student) }}" method="POST" class="inline" onsubmit="return confirm('Hapus siswa {{ $student->full_name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="action-btn" title="Hapus" style="color:var(--danger)">X</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="padding:40px;text-align:center;color:var(--on-surface-muted)">
                            @if(request('q'))
                                Tidak ada siswa yang cocok dengan pencarian "{{ request('q') }}".
                            @else
                                Belum ada siswa. Tambahkan atau import dari CSV.
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-sm);margin-top:var(--sp-lg)">
            <span class="text-sm text-muted">Menampilkan {{ $students->firstItem() ?? 0 }}-{{ $students->lastItem() ?? 0 }} dari {{ $students->total() }} siswa</span>
            <div class="pagination" style="margin-top:0">
                {{ $students->links() }}
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.student-cb');
            const form = document.getElementById('bulkPromoteForm');
            const counter = document.getElementById('selectedCount');

            function updateSelection() {
                const checked = document.querySelectorAll('.student-cb:checked').length;
                counter.textContent = checked;
                form.style.display = checked === 0 ? 'none' : '';
            }

            selectAll?.addEventListener('change', () => {
                checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
                updateSelection();
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateSelection);
            });
        });
    </script>
</x-app-layout>
