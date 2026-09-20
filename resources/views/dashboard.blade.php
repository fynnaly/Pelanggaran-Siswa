<x-app-layout>
    <x-slot name="header">

        <div class="page-header">
            <div>
                <h1>Dashboard</h1>
                <p class="text-muted text-sm">Ringkasan data pelanggaran dan pencapaian siswa</p>
            </div>
            <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap">
                <a href="{{ route('kasus-pelanggaran.create') }}" class="btn btn-primary"><i data-lucide="plus" class="icon-sm"></i> Buat Kasus</a>
                <a href="{{ route('students.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="search" class="icon-sm"></i> Cari Siswa</a>
                <a href="{{ route('students.export') }}" class="btn btn-secondary btn-sm"><i data-lucide="download" class="icon-sm"></i> Export</a>
            </div>
        </div>
    </x-slot>

    <div class="main-wrap">
        <div class="grid grid-4">
            <div class="card-glass">
                <div style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="users" class="icon-lg" style="color:var(--tertiary)"></i><div class="stat-label">Siswa Aktif</div></div>
                <div class="stat-value">{{ number_format($totalStudentsActive) }}</div>
                <div class="stat-change positive">{{ $activeAcademicYear?->name ?? 'Tahun ajaran aktif' }}</div>
            </div>
            <div class="card-glass">
                <div style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="alert-triangle" class="icon-lg" style="color:var(--accent)"></i><div class="stat-label">Ditemukan</div></div>
                <div class="stat-value" style="color:var(--accent)">{{ number_format($totalCasesFound) }}</div>
                <div class="stat-change negative">Perlu diverifikasi</div>
            </div>
            <div class="card-glass">
                <div style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="check-circle" class="icon-lg" style="color:var(--tertiary)"></i><div class="stat-label">Selesai</div></div>
                <div class="stat-value">{{ number_format($totalCasesDone) }}</div>
                @php $rate = $totalCases > 0 ? round(($totalCasesDone/$totalCases)*100) : 0; @endphp
                <div class="stat-change positive">{{ $rate }}% resolve rate</div>
            </div>
            <div class="card-glass">
                <div style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="tags" class="icon-lg" style="color:var(--primary)"></i><div class="stat-label">Kategori Aktif</div></div>
                <div class="stat-value">{{ $totalCategoriesActive }}/{{ $totalCategories }}</div>
                <div class="stat-change positive">Kategori pelanggaran</div>
            </div>
        </div>

        @if($totalCases > 0)
        <div class="card" style="margin-bottom:var(--sp-md)">
            <h3 style="font-size:0.875rem;font-weight:600;margin-bottom:var(--sp-sm)">Progres Penanganan Kasus</h3>
            @php
                $foundPct = ($totalCasesFound / $totalCases) * 100;
                $validatedPct = ($totalCasesValidated / $totalCases) * 100;
                $donePct = ($totalCasesDone / $totalCases) * 100;
                $dismissedPct = ($totalCasesDismissed / $totalCases) * 100;
            @endphp
            <div style="height:10px;background:var(--bs);border-radius:6px;overflow:hidden;display:flex">
                @if($foundPct > 0)
                    <div style="width:{{ $foundPct }}%;background:var(--accent)" title="Ditemukan: {{ $totalCasesFound }}"></div>
                @endif
                @if($validatedPct > 0)
                    <div style="width:{{ $validatedPct }}%;background:var(--primary)" title="Divalidasi: {{ $totalCasesValidated }}"></div>
                @endif
                @if($donePct > 0)
                    <div style="width:{{ $donePct }}%;background:var(--tertiary)" title="Selesai: {{ $totalCasesDone }}"></div>
                @endif
                @if($dismissedPct > 0)
                    <div style="width:{{ $dismissedPct }}%;background:var(--on-surface-muted)" title="Dibuang: {{ $totalCasesDismissed }}"></div>
                @endif
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:var(--sp-xs);font-size:0.75rem;color:var(--on-surface-muted)">
                <span>Ditemukan {{ $totalCasesFound }}</span>
                <span>Divalidasi {{ $totalCasesValidated }}</span>
                <span>Selesai {{ $totalCasesDone }}</span>
                <span>Dibuang {{ $totalCasesDismissed }}</span>
            </div>
        </div>
        @endif

        <div class="section">
            <div class="layout-2col">
                <div>
                    <div class="section-header">
                        <h2 style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="file-text" class="icon"></i> Kasus Terbaru</h2>
                        <a href="{{ route('kasus-pelanggaran.index') }}" class="btn btn-secondary btn-sm">Lihat Semua</a>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>No. Kasus</th><th>Siswa</th><th>Kategori</th><th>Poin</th><th>Status</th><th>Tanggal</th></tr></thead>
                            <tbody>
                                @forelse($recentCases as $case)
                                    <tr>
                                        <td class="mono">{{ $case->case_number }}</td>
                                        <td><strong>{{ $case->student?->full_name ?? '-' }}</strong><br><span class="text-muted text-sm">{{ $case->student?->schoolClass?->name ?? '' }}</span></td>
                                        <td>{{ $case->violationCategory?->name ?? '-' }}</td>
                                        <td class="mono">-{{ $case->violationCategory?->points ?? 0 }}</td>
                                        <td>
                                            @if($case->status==='found')<span class="badge badge-warning">Diproses</span>
                                            @elseif($case->status==='validated')<span class="badge" style="background:var(--ws);color:var(--wt)">Divalidasi</span>
                                            @elseif($case->status==='done')<span class="badge badge-success">Selesai</span>
                                            @else<span class="badge badge-danger">{{ ucfirst($case->status) }}</span>@endif
                                        </td>
                                        <td class="text-sm text-muted">{{ $case->created_at?->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>Belum ada kasus.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <div class="section-header">
                        <h2 style="display:flex;align-items:center;gap:var(--sp-sm)"><i data-lucide="users" class="icon"></i> Poin Rendah</h2>
                    </div>
                    <div class="card">
                        @forelse($lowPointStudents as $student)
                            @php $initials = collect(explode(' ',$student->full_name))->map(fn($w)=>strtoupper(substr($w,0,1)))->take(2)->implode(''); @endphp
                            <div class="list-item">
                                <div class="list-avatar">{{ $initials }}</div>
                                <div class="list-info">
                                    <div class="list-name">{{ $student->full_name }}</div>
                                    <div class="list-meta">{{ $student->schoolClass?->name ?? '-' }} &middot; {{ $student->nis }}</div>
                                </div>
                                <div class="list-score {{ $student->latest_balance<=500?'danger':'warning' }}">{{ $student->latest_balance }}</div>
                            </div>
                        @empty
                            <p class="text-muted text-sm" style="padding:var(--sp-md) 0"><i data-lucide="check-circle" class="icon-sm"></i> Tidak ada siswa dengan poin kritis.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
</x-app-layout>
