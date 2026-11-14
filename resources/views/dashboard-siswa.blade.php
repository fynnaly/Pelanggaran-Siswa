@extends('layouts.app')

@section('content')
    @php
        $statusLabels = [
            'found' => 'Temuan',
            'clarifying' => 'Klarifikasi',
            'classifying' => 'Klasifikasi',
            'handling' => 'Penanganan',
            'monitoring' => 'Pemantauan',
            'closed' => 'Selesai',
            'dismissed' => 'Dibatalkan',
        ];
        $statusBadges = [
            'found' => 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
            'clarifying' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
            'classifying' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300',
            'handling' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
            'monitoring' => 'bg-violet-50 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
            'closed' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
            'dismissed' => 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300',
        ];

        $zoneColors = [
            'danger' => [
                'badge' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/15 dark:text-red-300 dark:border-red-500/30',
                'bar' => 'bg-red-500',
                'text' => 'text-red-600 dark:text-red-400',
            ],
            'warning' => [
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:border-amber-500/30',
                'bar' => 'bg-amber-500',
                'text' => 'text-amber-600 dark:text-amber-400',
            ],
            'info' => [
                'badge' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-500/15 dark:text-sky-300 dark:border-sky-500/30',
                'bar' => 'bg-sky-500',
                'text' => 'text-sky-600 dark:text-sky-400',
            ],
            'success' => [
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/30',
                'bar' => 'bg-emerald-500',
                'text' => 'text-emerald-600 dark:text-emerald-400',
            ],
            'primary' => [
                'badge' => 'bg-brand-50 text-brand-700 border-brand-200 dark:bg-brand-500/15 dark:text-brand-300 dark:border-brand-500/30',
                'bar' => 'bg-brand-500',
                'text' => 'text-brand-600 dark:text-brand-400',
            ],
        ];

        $colorKey = $zone['color'] ?? 'success';
        $currentTheme = $zoneColors[$colorKey] ?? $zoneColors['success'];
        $studentClass = $student?->currentEnrollment?->schoolClass?->name ?? 'Belum terdaftar kelas';
        $homeroomName = $homeroomTeacher?->name ?? 'Belum ditentukan';
        $percentBalance = $defaultBalance > 0 ? min(100, max(0, round(($balance / $defaultBalance) * 100))) : 100;
    @endphp

    <div class="space-y-6">
        <!-- 1. Header Profil Siswa -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                        <i data-lucide="user" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ $student?->full_name ?? auth()->user()->name }}</h1>
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                {{ $studentClass }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-x-2 gap-y-1">
                            @if($student?->nisn)
                                <span>NISN: <strong class="text-gray-700 dark:text-gray-200">{{ $student->nisn }}</strong></span>
                                <span>&bull;</span>
                            @endif
                            @if($student?->nis)
                                <span>NIS: <strong class="text-gray-700 dark:text-gray-200">{{ $student->nis }}</strong></span>
                                <span>&bull;</span>
                            @endif
                            <span>Wali Kelas: <strong class="text-gray-700 dark:text-gray-200">{{ $homeroomName }}</strong></span>
                            <span>&bull;</span>
                            <span>{{ $activeAcademicYear?->name ?? 'Tahun Ajaran Aktif' }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if($zone)
                        <span class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs sm:text-sm font-semibold {{ $currentTheme['badge'] }}">
                            <i data-lucide="shield" class="w-4 h-4"></i>
                            Zona {{ $zone['name'] }}
                        </span>
                    @endif
                    <a href="{{ $student ? route('point-ledgers.siswa', $student) : '#' }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 transition">
                        <i data-lucide="book-open" class="w-4 h-4"></i>
                        <span>Buku Kas Poin</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. Hero Card: Status Saldo Poin Disiplin -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Saldo Poin Disiplin</span>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl sm:text-5xl font-extrabold tracking-tight {{ $currentTheme['text'] }}">
                            {{ number_format($balance) }}
                        </span>
                        <span class="text-sm sm:text-base font-medium text-gray-400 dark:text-gray-500">/ {{ number_format($defaultBalance) }} poin</span>
                    </div>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        @if($zone && ($zone['responsible'] ?? null))
                            Penanggung jawab pembinaan: <strong class="text-gray-700 dark:text-gray-200">{{ $zone['responsible'] }}</strong>
                            @if($zone['action'] ?? null) &mdash; {{ $zone['action'] }} @endif
                        @else
                            Pertahankan poin Anda dengan selalu mematuhi tata tertib sekolah.
                        @endif
                    </p>
                </div>

                <div class="w-full md:max-w-xs space-y-2">
                    <div class="flex justify-between text-xs font-medium text-gray-600 dark:text-gray-400">
                        <span>Tingkat Kepatuhan</span>
                        <span>{{ $percentBalance }}%</span>
                    </div>
                    <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div class="h-full rounded-full transition-all duration-500 {{ $currentTheme['bar'] }}" style="width: {{ $percentBalance }}%"></div>
                    </div>
                    <div class="flex justify-between text-[11px] text-gray-400 dark:text-gray-500">
                        <span>Kritis (&lt;1500)</span>
                        <span>Waspada (1500-1799)</span>
                        <span>Aman (1800+)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Dynamic Alert Banner: Status Kasus Aktif -->
        @if($activeCaseCount > 0)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5 dark:border-amber-500/30 dark:bg-amber-500/10">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">
                        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                    </div>
                    <div class="space-y-1">
                        <h2 class="text-sm sm:text-base font-semibold text-amber-900 dark:text-amber-200">
                            Perhatian: Terdapat {{ $activeCaseCount }} Kasus Disiplin Aktif
                        </h2>
                        <p class="text-xs sm:text-sm text-amber-800/90 dark:text-amber-300/90">
                            Anda memiliki catatan pelanggaran yang sedang dalam tahap penanganan atau pembinaan. Silakan hubungi Guru BK atau Wali Kelas Anda untuk menyelesaikan tindak lanjut.
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 sm:p-5 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                        <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                    </div>
                    <div class="space-y-1">
                        <h2 class="text-sm sm:text-base font-semibold text-emerald-900 dark:text-emerald-200">
                            Status Disiplin Bersih
                        </h2>
                        <p class="text-xs sm:text-sm text-emerald-800/90 dark:text-emerald-300/90">
                            Tidak ada kasus pelanggaran aktif. Pertahankan dedikasi, kedisiplinan, dan terus tingkatkan prestasi belajar Anda!
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- 4. Ringkasan Kartu Metrik (KPI) -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Poin Saat Ini</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($balance) }}</p>
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $zone['name'] ?? 'Zona Standar' }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    <i data-lucide="award" class="w-6 h-6"></i>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Kasus Aktif</p>
                    <p class="mt-1 text-2xl font-bold {{ $activeCaseCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">
                        {{ $activeCaseCount }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">dalam pembinaan</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                    <i data-lucide="shield-alert" class="w-6 h-6"></i>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Kasus</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $totalCaseYear }}</p>
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">tahun ajaran ini</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400">
                    <i data-lucide="file-warning" class="w-6 h-6"></i>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Prestasi Diraih</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $myAchievements->count() }}</p>
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">tercatat di sistem</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                    <i data-lucide="trophy" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- 5. Grid Konten: Kasus Terkini & Prestasi Terkini -->
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <!-- Kasus Pelanggaran -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400">
                            <i data-lucide="alert-circle" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Riwayat Pelanggaran</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Daftar kasus disiplin Anda</p>
                        </div>
                    </div>
                    <a href="{{ route('kasus-pelanggaran.index') }}" class="text-xs sm:text-sm font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 flex items-center gap-1">
                        <span>Lihat Semua</span>
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($myCases as $case)
                        @php
                            $effectivePoints = $case->getEffectivePoints();
                        @endphp
                        <a href="{{ route('kasus-pelanggaran.show', $case) }}" class="flex items-center justify-between gap-3 p-4 sm:px-5 hover:bg-gray-50 dark:hover:bg-white/5 transition">
                            <div class="min-w-0 space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $case->violationCategory?->name ?? 'Pelanggaran' }}
                                    </p>
                                    @if($effectivePoints > 0)
                                        <span class="inline-flex items-center rounded-md bg-red-50 px-1.5 py-0.5 text-xs font-bold text-red-600 dark:bg-red-500/15 dark:text-red-400">
                                            -{{ $effectivePoints }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                    <span class="font-mono">{{ $case->case_number }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $case->incident_date?->format('d M Y') ?? '-' }}</span>
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusBadges[$case->status] ?? 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300' }}">
                                {{ $statusLabels[$case->status] ?? $case->status }}
                            </span>
                        </a>
                    @empty
                        <div class="px-5 py-12 text-center space-y-2">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                                <i data-lucide="shield-check" class="w-6 h-6"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Tidak Ada Catatan Pelanggaran</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                                Luar biasa! Buku pelanggaran Anda kosong. Terus jaga kedisiplinan diri dan nama baik sekolah.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Prestasi & Rekam Positif -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                            <i data-lucide="medal" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Rekam Prestasi</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Pencapaian dan kegiatan positif</p>
                        </div>
                    </div>
                    <a href="{{ route('achievement-records.index') }}" class="text-xs sm:text-sm font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 flex items-center gap-1">
                        <span>Lihat Semua</span>
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($myAchievements as $record)
                        <a href="{{ route('achievement-records.show', $record) }}" class="flex items-center justify-between gap-3 p-4 sm:px-5 hover:bg-gray-50 dark:hover:bg-white/5 transition">
                            <div class="min-w-0 space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $record->achievementCategory?->name ?? 'Prestasi' }}
                                    </p>
                                    @if($record->achievementCategory?->points)
                                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-1.5 py-0.5 text-xs font-bold text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                                            +{{ $record->achievementCategory->points }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $record->created_at?->format('d M Y') ?? '-' }} &bull; Perekam: {{ $record->recorder?->name ?? 'Staf' }}
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $record->status === 'verified' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : ($record->status === 'rejected' ? 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300') }}">
                                {{ $record->status === 'verified' ? 'Diverifikasi' : ($record->status === 'rejected' ? 'Ditolak' : 'Pending') }}
                            </span>
                        </a>
                    @empty
                        <div class="px-5 py-12 text-center space-y-2">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                <i data-lucide="award" class="w-6 h-6"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Belum Ada Rekam Prestasi</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                                Ikuti perlombaan, kegiatan ekstrakurikuler, atau aksi positif sekolah agar prestasi Anda tercatat di sini.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- 6. Riwayat Transaksi Mutasi Poin Terakhir -->
        @if($recentLedgers->isNotEmpty())
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400">
                            <i data-lucide="history" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Mutasi Poin Terakhir</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">5 riwayat perubahan saldo poin terbaru</p>
                        </div>
                    </div>
                    <a href="{{ $student ? route('point-ledgers.siswa', $student) : '#' }}" class="text-xs sm:text-sm font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 flex items-center gap-1">
                        <span>Buku Kas Lengkap</span>
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($recentLedgers as $item)
                        @php
                            $isCredit = ($item->points ?? 0) >= 0;
                            $diff = abs($item->points ?? 0);
                        @endphp
                        <div class="flex items-center justify-between gap-4 p-4 sm:px-5">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $isCredit ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400' : 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400' }}">
                                    <i data-lucide="{{ $isCredit ? 'arrow-up-right' : 'arrow-down-left' }}" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0 space-y-0.5">
                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $item->notes ?? ($isCredit ? 'Poin Penghargaan' : 'Pelanggaran Disiplin') }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ $item->created_at?->format('d M Y, H:i') ?? '-' }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold {{ $isCredit ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $isCredit ? '+' : '-' }}{{ number_format($diff) }}
                                </p>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500">
                                    Sisa: {{ number_format($item->balance_after ?? 0) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
