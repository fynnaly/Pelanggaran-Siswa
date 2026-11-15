<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1>Riwayat Pelanggaran dan Prestasi</h1>
                <p class="text-muted text-sm">Pilih jenis laporan: per kelas atau per siswa. Rekap berisi bukti pelanggaran, prestasi, dan transaksi poin.</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="#kelas" class="btn btn-secondary"><i data-lucide="users" class="icon-sm"></i> Per Kelas</a>
                <a href="#siswa" class="btn btn-secondary"><i data-lucide="user" class="icon-sm"></i> Per Siswa</a>
                <a href="{{ route('laporan.pelanggaran', $linkParams) }}" class="btn btn-secondary">Statistik Pelanggaran</a>
            </div>
        </div>
    </x-slot>

    <div class="main-wrap">
        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        {{-- ===== BAGIAN 1: LAPORAN PER KELAS ===== --}}
        <section id="kelas" style="margin-bottom:var(--sp-lg)">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:var(--sp-sm);flex-wrap:wrap;margin-bottom:var(--sp-sm)">
                <div>
                    <h2 style="margin:0;font-weight:700;display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="users" class="icon-sm"></i> Riwayat per Kelas</h2>
                    <p class="text-muted text-sm" style="margin:2px 0 0">Rekap gabungan seluruh siswa dalam satu kelas — siap dicetak/diarsipkan per kelas.</p>
                </div>
                <a href="{{ route('laporan.per-kelas', $linkParams) }}" class="btn btn-secondary btn-sm"><i data-lucide="bar-chart-3" class="icon-sm"></i> Lihat ringkasan chart semua kelas</a>
            </div>
            <div class="card">
                @if($classes->isEmpty())
                    <p class="text-muted text-sm">Tidak ada kelas yang tersedia dalam cakupan akses Anda.</p>
                @else
                    <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                        @foreach($classes as $schoolClass)
                            <a class="btn btn-secondary" href="{{ route('laporan.kelas', array_merge(['schoolClass' => $schoolClass->id], $linkParams)) }}">
                                {{ $schoolClass->name }}{{ $schoolClass->academicYear ? ' · '.$schoolClass->academicYear->name : '' }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- ===== BAGIAN 2: LAPORAN PER SISWA ===== --}}
        <section id="siswa">
            <div style="margin-bottom:var(--sp-sm)">
                <h2 style="margin:0;font-weight:700;display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="user" class="icon-sm"></i> Riwayat per Siswa</h2>
                <p class="text-muted text-sm" style="margin:2px 0 0">Cari siswa, lalu buka riwayat individualnya.</p>
            </div>
            <div class="toolbar">
                <form method="GET" action="{{ route('laporan.riwayat') }}#siswa" style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;align-items:center;width:100%">
                    <input type="search" name="search" class="input" value="{{ $search }}" placeholder="Cari nama, NIS, atau NISN" aria-label="Cari siswa">
                    <select name="year" class="input" aria-label="Tahun ajaran">
                        <option value="">Semua tahun ajaran</option>
                        @foreach($years as $year)
                            <option value="{{ $year->id }}" {{ (string) $filters['year'] === (string) $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                        @endforeach
                    </select>
                    <select name="class" class="input" aria-label="Kelas">
                        <option value="">Semua kelas</option>
                        @foreach($classes as $schoolClass)
                            <option value="{{ $schoolClass->id }}" {{ (string) $filters['class'] === (string) $schoolClass->id ? 'selected' : '' }}>{{ $schoolClass->name }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="from" class="input" value="{{ $filters['from'] }}" aria-label="Tanggal mulai">
                    <input type="date" name="to" class="input" value="{{ $filters['to'] }}" aria-label="Tanggal selesai">
                    <button type="submit" class="btn btn-primary btn-sm">Terapkan filter</button>
                    <a href="{{ route('laporan.riwayat') }}#siswa" class="btn btn-secondary btn-sm">Reset</a>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Siswa</th><th>NIS</th><th>Kelas saat ini</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td>
                                    <strong>{{ $student->full_name }}</strong>
                                    <div class="text-muted text-sm">NISN: {{ $student->nisn ?? '-' }}</div>
                                </td>
                                <td class="mono">{{ $student->nis }}</td>
                                <td>{{ $student->currentEnrollment?->schoolClass?->name ?? '-' }}</td>
                                <td>
                                    <a class="btn btn-secondary btn-sm" href="{{ route('laporan.siswa', array_merge(['student' => $student->id], $linkParams)) }}">Lihat riwayat</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted" style="text-align:center;padding:var(--sp-lg)">Tidak ada siswa yang cocok dengan pencarian dan cakupan akses ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $students->links() }}</div>
        </section>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
