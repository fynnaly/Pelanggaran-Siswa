<x-app-layout>
    <x-slot name="header">
        <div class="breadcrumb">Beranda / Dashboard</div>
        <div class="page-header">
            <h1>Dashboard</h1>
            <p class="text-muted text-sm">Ringkasan data pelanggaran dan pencapaian siswa</p>
        </div>
    </x-slot>

    <div class="main-wrap">
        {{-- Stat Cards --}}
        <div class="grid grid-4">
            <div class="card-glass">
                <div class="stat-label">Total Siswa Aktif</div>
                <div class="stat-value">{{ number_format($totalStudentsActive) }}</div>
                <div class="stat-change positive">{{ $activeAcademicYear?->name ?? 'Tahun ajaran aktif' }}</div>
            </div>
            <div class="card-glass">
                <div class="stat-label">Kasus Ditemukan</div>
                <div class="stat-value">{{ number_format($totalCasesFound) }}</div>
                <div class="stat-change negative">Perlu diverifikasi</div>
            </div>
            <div class="card-glass">
                <div class="stat-label">Kasus Selesai</div>
                <div class="stat-value">{{ number_format($totalCasesDone) }}</div>
                @php $resolveRate = $totalCases > 0 ? round(($totalCasesDone / $totalCases) * 100) : 0; @endphp
                <div class="stat-change positive">{{ $resolveRate }}% resolve rate</div>
            </div>
            <div class="card-glass">
                <div class="stat-label">Kategori Aktif</div>
                <div class="stat-value">{{ $totalCategoriesActive }} / {{ $totalCategories }}</div>
                <div class="stat-change positive">Kategori pelanggaran</div>
            </div>
        </div>

        {{-- Breakdown status --}}
        <div class="section">
            <div class="grid grid-4">
                <div class="card-glass">
                    <div class="stat-label">Ditemukan</div>
                    <div class="stat-value" style="color:var(--tertiary)">{{ $totalCasesFound }}</div>
                </div>
                <div class="card-glass">
                    <div class="stat-label">Divalidasi</div>
                    <div class="stat-value" style="color:var(--tertiary)">{{ $totalCasesValidated }}</div>
                </div>
                <div class="card-glass">
                    <div class="stat-label">Selesai</div>
                    <div class="stat-value">{{ $totalCasesDone }}</div>
                </div>
                <div class="card-glass">
                    <div class="stat-label">Dibuang</div>
                    <div class="stat-value" style="color:var(--on-surface-muted)">{{ $totalCasesDismissed }}</div>
                </div>
            </div>
        </div>

        {{-- Kasus Terbaru + Siswa Poin Rendah --}}
        <div class="section">
            <div class="layout-2col">
                <div>
                    <div class="section-header">
                        <h2>Kasus Terbaru</h2>
                        <a href="{{ route('discipline-cases.index') }}" class="btn btn-secondary btn-sm">Lihat Semua</a>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>No. Kasus</th>
                                    <th>Siswa</th>
                                    <th>Kategori</th>
                                    <th>Poin</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentCases as $case)
                                    <tr>
                                        <td class="mono">{{ $case->case_number }}</td>
                                        <td>
                                            <strong>{{ $case->student?->full_name ?? '-' }}</strong>
                                            <br><span class="text-muted text-sm">{{ $case->student?->schoolClass?->name ?? '' }}</span>
                                        </td>
                                        <td>{{ $case->violationCategory?->name ?? '-' }}</td>
                                        <td class="mono">-{{ $case->violationCategory?->points ?? 0 }}</td>
                                        <td>
                                            @if($case->status === 'found')
                                                <span class="badge badge-warning">Diproses</span>
                                            @elseif($case->status === 'validated')
                                                <span class="badge badge-warning">Divalidasi</span>
                                            @elseif($case->status === 'done')
                                                <span class="badge badge-success">Selesai</span>
                                            @else
                                                <span class="badge badge-danger">{{ ucfirst($case->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-sm text-muted">{{ $case->created_at?->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" style="padding:40px;text-align:center;color:var(--on-surface-muted)">Belum ada kasus.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <div class="section-header">
                        <h2>Siswa Poin Rendah</h2>
                    </div>
                    <div class="card">
                        @forelse($lowPointStudents as $student)
                            @php
                                $initials = collect(explode(' ', $student->full_name))->map(fn($w) => strtoupper(substr($w,0,1)))->take(2)->implode('');
                            @endphp
                            <div class="list-item">
                                <div class="list-avatar">{{ $initials }}</div>
                                <div class="list-info">
                                    <div class="list-name">{{ $student->full_name }}</div>
                                    <div class="list-meta">{{ $student->schoolClass?->name ?? '-' }} &middot; {{ $student->nis }}</div>
                                </div>
                                <div class="list-score {{ $student->latest_balance <= 500 ? 'danger' : 'warning' }}">{{ $student->latest_balance }}</div>
                            </div>
                        @empty
                            <p class="text-muted text-sm" style="padding:var(--sp-md) 0">Tidak ada siswa dengan poin kritis.</p>
                        @endforelse
                    </div>

                    <div class="section" style="margin-top:var(--sp-lg)">
                        <div class="section-header">
                            <h2>Aksi Cepat</h2>
                        </div>
                        <div class="card" style="display:flex;flex-direction:column;gap:var(--sp-sm)">
                            <a href="{{ route('discipline-cases.create') }}" class="btn btn-primary" style="width:100%;justify-content:center">Buat Kasus Baru</a>
                            <a href="{{ route('students.index') }}" class="btn btn-secondary" style="width:100%;justify-content:center">Cari Siswa</a>
                            <a href="{{ route('students.export') }}" class="btn btn-secondary" style="width:100%;justify-content:center">Export Laporan</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
