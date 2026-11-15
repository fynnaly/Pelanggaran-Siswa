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
        $studentName = $student?->full_name ?? auth()->user()->name;

        // Pesan edukatif dan suportif berbasis zona sekolah
        $zoneMotivation = match (true) {
            $balance >= 2500 => 'Luar biasa! Kamu berada di Zona Istimewa, berpeluang meraih Sertifikat.',
            $balance >= 2200 => 'Hebat! Poin prestasimu melampaui standar. Terus kembangkan bakat dan keteladananmu.',
            $balance >= 1900 => 'Karakter dan kedisiplinanmu sangat baik. Terus pertahankan dan kumpulkan poin prestasi dari kegiatan positif!',
            $balance >= 1500 => 'Kamu sedang dalam pendampingan pembinaan karakter bersama ' . ($zone['responsible'] ?? 'Bapak/Ibu Guru') . '. Poin dapat dipulihkan melalui kegiatan penebusan dan prestasi.',
            default => 'Poinmu memerlukan perhatian khusus. Segera konsultasikan langkah pemulihan bersama Guru BK dan Wali Kelas.',
        };
    @endphp

    <div class="space-y-6">
        <!-- 1. Header Profil Siswa -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 font-bold text-xl">
                        @if(auth()->user()?->avatar_path)
                            <img src="{{ asset('storage/'.auth()->user()->avatar_path) }}" alt="Foto profil {{ $studentName }}" class="h-full w-full object-cover">
                        @else
                            {{ strtoupper(substr($studentName, 0, 2)) }}
                        @endif
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ $studentName }}</h1>
                        </div>
                        <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-x-2 gap-y-1">
                            @if($student?->nis)
                                <span>NIS: <strong class="text-gray-700 dark:text-gray-200">{{ $student->nis }}</strong></span>
                                <span>&bull;</span>
                            @endif
                            @if($student?->nisn)
                                <span>Kelas: <strong class="text-gray-700 dark:text-gray-200">{{ $studentClass }}</strong></span>
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
                            Zona {{ $zone['name'] }}
                        </span>
                    @endif
                    <a href="{{ $student ? route('point-ledgers.siswa', $student) : '#' }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 transition">
                        <span>Lihat Riwayat Poin</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. Hero Card: Status Poin Karakter Siswa -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-1.5 max-w-xl">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-1.5">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-brand-500"></i>
                        Poin Karakter dan Prestasi
                    </span>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl sm:text-5xl font-extrabold tracking-tight {{ $currentTheme['text'] }}">
                            {{ number_format($balance) }}
                        </span>
                        <span class="text-sm sm:text-base font-medium text-gray-400 dark:text-gray-500">/ {{ number_format($defaultBalance) }} Poin Standar</span>
                    </div>
                    <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 leading-relaxed pt-1">
                        {{ $zoneMotivation }}
                    </p>
                </div>

                <!-- Bagian Jenjang Sikap & Progres Karakter (Ramah Siswa, Non-Korporat) -->
                <div class="w-full lg:max-w-sm space-y-2.5 rounded-xl bg-gray-50 p-4 dark:bg-white/5 border border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between text-xs font-semibold">
                        <span class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300">
                            <span>Jenjang Sikap: <strong class="{{ $currentTheme['text'] }}">{{ $zone['name'] ?? 'Standar' }}</strong></span>
                        </span>
                        <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">
                            @if($pointsToNext > 0)
                                Kurang {{ number_format($pointsToNext) }} poin lagi
                            @else
                                {{ $nextTargetName ?? 'Pencapaian Tertinggi' }}
                            @endif
                        </span>
                    </div>

                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-full rounded-full transition-all duration-500 {{ $currentTheme['bar'] }}" style="width: {{ $visualProgress ?? 100 }}%"></div>
                    </div>

                    <div class="flex justify-between text-[11px] font-medium text-gray-500 dark:text-gray-400 pt-0.5">
                        <span class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                            Bimbingan (&lt;1800)
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            Standar Baik (1900-2199)
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-400"></span>
                            Berprestasi (2200+)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Dynamic Alert Banner: Status Bimbingan & Kedisiplinan -->
        @if($activeCaseCount > 0)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5 dark:border-amber-500/30 dark:bg-amber-500/10">
                <div class="flex items-start gap-3">
                    <div class="space-y-1">
                        <h2 class="text-sm sm:text-base font-semibold text-amber-900 dark:text-amber-200">
                            Catatan Sikap Dalam Pendampingan ({{ $activeCaseCount }} Catatan Aktif)
                        </h2>
                        <p class="text-xs sm:text-sm text-amber-800/90 dark:text-amber-300/90 leading-relaxed">
                            Kamu memiliki catatan kedisiplinan yang sedang didampingi oleh Guru BK atau Wali Kelas. Tetap semangat, bicarakan rencana pemulihan dengan Bapak/Ibu Guru dan ikuti kegiatan positif sekolah untuk mengembalikan poinmu.
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 sm:p-5 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                <div class="flex items-start gap-3">
                    <div class="space-y-1">
                        <h2 class="text-sm sm:text-base font-semibold text-emerald-900 dark:text-emerald-200">
                            Karakter dan Kedisiplinan Terjaga Sangat Baik
                        </h2>
                        <p class="text-xs sm:text-sm text-emerald-800/90 dark:text-emerald-300/90 leading-relaxed">
                            Bagus sekali! Tidak ada catatan pelanggaran aktif. Terus pertahankan kedisiplinan, kejujuran, dan sopan santun dalam belajar sehari-hari.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- 4. Ringkasan Poin & Prestasi Siswa -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Poin Karakter</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($balance) }}</p>
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Zona {{ $zone['name'] ?? 'Standar' }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Prestasi Diraih</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $myAchievements->count() }}</p>
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                        @if(($totalAchievementPoints ?? 0) > 0)
                            +{{ number_format($totalAchievementPoints) }} poin terkumpul
                        @else
                            tercatat di sistem
                        @endif
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Status Bimbingan</p>
                    <p class="mt-1 text-2xl font-bold {{ $activeCaseCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">
                        {{ $activeCaseCount > 0 ? $activeCaseCount . ' Catatan' : 'Tuntas' }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                        {{ $activeCaseCount > 0 ? 'dalam pendampingan' : 'tidak ada kasus aktif' }}
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Catatan Sikap</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $totalCaseYear }}</p>
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">tahun ajaran berjalan</p>
                </div>
            </div>
        </div>

        <!-- 5. Kotak Panduan Edukatif: Cara Menambah & Memulihkan Poin Karakter -->
        <div class="rounded-2xl border border-brand-100 bg-brand-50/50 p-5 dark:border-brand-500/20 dark:bg-brand-500/5">
            <div class="flex items-start gap-3.5">
                <div class="space-y-2">
                    <div>
                        <h2 class="text-sm sm:text-base font-semibold text-gray-900 dark:text-white">
                            Panduan Karakter: Cara Menambah dan Memulihkan Poinmu
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Sistem poin sekolah bukan sekadar catatan, melainkan sarana pertumbuhan karakter dan apresiasi prestasi belajarmu.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1 text-xs">
                        <div class="rounded-xl bg-white p-3 border border-gray-200/80 dark:bg-gray-900 dark:border-gray-800 space-y-1">
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                Raih Prestasi
                            </span>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                                Ikuti lomba akademik, olahraga, seni, ekskul, dan aksi sosial untuk mendapatkan tambahan poin penghargaan.
                            </p>
                        </div>
                        <div class="rounded-xl bg-white p-3 border border-gray-200/80 dark:bg-gray-900 dark:border-gray-800 space-y-1">
                            <span class="font-semibold text-sky-600 dark:text-sky-400 flex items-center gap-1">
                                Tuntaskan Pemulihan
                            </span>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                                Jika memiliki catatan pelanggaran, konsultasikan dengan Wali Kelas / Guru BK untuk menyelesaikan kegiatan pembinaan.
                            </p>
                        </div>
                        <div class="rounded-xl bg-white p-3 border border-gray-200/80 dark:bg-gray-900 dark:border-gray-800 space-y-1">
                            <span class="font-semibold text-brand-600 dark:text-brand-400 flex items-center gap-1">
                                Jaga Keteladanan
                            </span>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                                Hadir tepat waktu, kenakan seragam rapi, dan saling menghormati di lingkungan sekolah setiap hari.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Grid Konten: Catatan Kedisiplinan & Rekam Prestasi -->
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <!-- Catatan Kedisiplinan -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div class="flex items-center gap-2.5">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Catatan Kedisiplinan</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Daftar catatan sikap dan pembinaan karakter</p>
                        </div>
                    </div>
                    <a href="{{ route('kasus-pelanggaran.index') }}" class="text-xs sm:text-sm font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 flex items-center gap-1">
                        <span>Lihat Semua</span>
                    </a>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($myCases as $case)
                        @php
                            $effectivePoints = $case->getEffectivePoints();
                        @endphp
                        <a href="{{ route('kasus-pelanggaran.show', $case) }}" class="flex items-center justify-between gap-3 p-4 sm:px-5 hover:bg-gray-50 dark:hover:bg-white/5 transition">
                            <div class="min-w-0 space-y-0.5">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $case->violationCategory?->name ?? 'Pelanggaran' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                    <span class="font-mono">{{ $case->case_number }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $case->incident_date?->locale('id')->translatedFormat('d M Y') ?? '-' }}</span>
                                </p>
                            </div>
                            {{-- Poin & status berdampingan di kanan agar posisinya konsisten dengan panel prestasi --}}
                            <div class="shrink-0 flex items-center gap-2">
                                @if($effectivePoints > 0)
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-1.5 py-0.5 text-xs font-bold text-red-600 dark:bg-red-500/15 dark:text-red-400">
                                        -{{ $effectivePoints }}
                                    </span>
                                @endif
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusBadges[$case->status] ?? 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300' }}">
                                    {{ $statusLabels[$case->status] ?? $case->status }}
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="px-5 py-12 text-center space-y-2">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Buku Catatan Kedisiplinan Bersih</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                                Luar biasa! Tidak ada catatan pelanggaran. Terus jaga kedisiplinan diri dan nama baik sekolah.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Rekam Prestasi & Pencapaian Positif -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div class="flex items-center gap-2.5">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Pencapaian dan Prestasi</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Aktivitas positif, piagam, dan penghargaan yang diraih</p>
                        </div>
                    </div>
                    <a href="{{ route('achievement-records.index') }}" class="text-xs sm:text-sm font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 flex items-center gap-1">
                        <span>Lihat Semua</span>
                    </a>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($myAchievements as $record)
                        <a href="{{ route('achievement-records.show', $record) }}" class="flex items-center justify-between gap-3 p-4 sm:px-5 hover:bg-gray-50 dark:hover:bg-white/5 transition">
                            <div class="min-w-0 space-y-0.5">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $record->achievementCategory?->name ?? 'Prestasi' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $record->created_at?->copy()->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y') ?? '-' }} &bull; Perekam: {{ $record->recorder?->name ?? 'Bapak/Ibu Guru' }}
                                </p>
                            </div>
                            {{-- Poin & status berdampingan di kanan agar posisinya konsisten dengan panel kedisiplinan --}}
                            <div class="shrink-0 flex items-center gap-2">
                                @if($record->achievementCategory?->points)
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-1.5 py-0.5 text-xs font-bold text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                                        +{{ $record->achievementCategory->points }}
                                    </span>
                                @endif
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $record->status === 'verified' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : ($record->status === 'rejected' ? 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300') }}">
                                    {{ $record->status === 'verified' ? 'Diverifikasi' : ($record->status === 'rejected' ? 'Ditolak' : 'Menunggu Validasi') }}
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="px-5 py-12 text-center space-y-2">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Belum Ada Rekam Prestasi</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                                Ikuti perlombaan, kegiatan ekstrakurikuler, atau aksi positif sekolah agar prestasimu tercatat di sini!
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- 7. Aktivitas Catatan Poin Terbaru -->
        @if($recentLedgers->isNotEmpty())
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div class="flex items-center gap-2.5">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Catatan Poin Terbaru</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">5 riwayat penambahan poin prestasi dan catatan kedisiplinan terbaru</p>
                        </div>
                    </div>
                    <a href="{{ $student ? route('point-ledgers.siswa', $student) : '#' }}" class="text-xs sm:text-sm font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 flex items-center gap-1">
                        <span>Lihat Riwayat Poin Lengkap</span>
                    </a>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($recentLedgers as $item)
                        @php
                            $isCredit = $item->direction === 'credit';
                            $reason = $item->reason ?: match ($item->transaction_type) {
                                'VIOLATION' => 'Catatan Kedisiplinan',
                                'ACHIEVEMENT', 'RECOVERY' => 'Poin Penghargaan / Prestasi',
                                default => 'Aktivitas Poin',
                            };
                        @endphp
                        <div class="flex items-center justify-between gap-4 p-4 sm:px-5">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="min-w-0 space-y-0.5">
                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $reason }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ $item->created_at?->copy()->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i') ?? '-' }} WIB
                                    </p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold {{ $isCredit ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $isCredit ? '+' : '-' }}{{ number_format($item->amount) }}
                                </p>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500">
                                    Poin Akhir: {{ number_format($item->balance_after ?? 0) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
