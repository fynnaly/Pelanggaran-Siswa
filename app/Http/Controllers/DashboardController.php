<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesWaliKelas;
use App\Models\AcademicYear;
use App\Models\DisciplineCase;
use App\Models\Student;
use App\Models\ViolationCategory;
use App\Support\ViolationStats;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ScopesWaliKelas;

    public function index(): View
    {
        // Siswa murni dapat dashboard khusus (data pribadi) — keluar sebelum
        // query statistik sekolah dijalankan.
        $user = auth()->user();
        $isPureSiswa = $user && $user->hasRole('siswa') && ! $user->hasAnyRole(['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah']);
        if ($isPureSiswa) {
            return $this->studentDashboard($user, AcademicYear::where('is_active', true)->first());
        }

        // Guru/wali kelas dapat dashboard pemantauan kelas (tanpa statistik sekolah penuh).
        $isGuru = $user && $user->hasAnyRole(['guru', 'wali_kelas']) && ! $user->hasAnyRole(['admin', 'bk', 'kesiswaan', 'kepala_sekolah']);
        if ($isGuru) {
            return $this->teacherDashboard($user, AcademicYear::where('is_active', true)->first());
        }

        $totalStudentsActive = Student::where('status', 'active')->count();

        $totalCasesFound = DisciplineCase::whereIn('status', ['found', 'clarifying', 'classifying'])->count();
        $totalCasesValidated = DisciplineCase::whereIn('status', ['handling', 'monitoring'])->count();
        $totalCasesDone = DisciplineCase::where('status', 'closed')->count();
        $totalCases = DisciplineCase::count();
        $totalCasesDismissed = DisciplineCase::where('status', 'dismissed')->count();

        $totalCategories = ViolationCategory::count();
        $totalCategoriesActive = ViolationCategory::where('status', 'active')->count();

        // Tahun ajaran aktif untuk pemantauan periode berjalan
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        // Siswa di zona Intervensi Khusus (< 1500) — pembinaannya Kepala Sekolah
        // (Program Kerja Kesiswaan 2026-2027). Ambil batas atas zona terendah dari
        // config/zones.php supaya angka tidak tersebar di banyak tempat.
        $intervensiMax = collect(config('zones.zones', []))
            ->first(fn (array $z) => $z['min'] === null)['max'] ?? 1499;

        // Saldo terakhir per siswa aktif — 1 query, tanpa N+1.
        $latestBalances = DB::table('point_ledgers')
            ->join(DB::raw('(SELECT student_id, MAX(id) AS max_id FROM point_ledgers GROUP BY student_id) lb'),
                function ($j) { $j->on('point_ledgers.id', '=', 'lb.max_id'); })
            ->join('students', 'students.id', '=', 'point_ledgers.student_id')
            ->where('students.status', 'active')
            ->pluck('point_ledgers.balance_after', 'point_ledgers.student_id');

        $criticalStudentsCount = $latestBalances
            ->filter(fn ($bal) => (int) $bal <= $intervensiMax)
            ->count();

        $lowPointStudents = Student::query()
            ->where('status', 'active')
            ->whereIn('id', $latestBalances->keys()->all())
            ->with(['user', 'currentEnrollment.schoolClass'])
            ->get()
            ->map(function (Student $s) use ($latestBalances) {
                $s->latest_balance = (int) ($latestBalances[$s->id] ?? 0);
                return $s;
            })
            ->filter(fn (Student $s) => $s->latest_balance <= $intervensiMax)
            ->sortBy('latest_balance')
            ->take(5)
            ->values();

        $recentCases = DisciplineCase::with(['student.currentEnrollment.schoolClass', 'schoolClass', 'violationCategory'])
            ->latest('created_at')
            ->limit(6)
            ->get();

        // Distribusi zona poin — pakai $latestBalances yang sudah ada (0 query tambahan).
        $balances = $latestBalances->values();

        $zoneCounts = [];
        foreach (config('zones.zones', []) as $z) {
            $zoneCounts[$z['name']] = ['count' => 0, 'color' => $z['color'], 'range' => $this->zoneRange($z)];
        }
        foreach ($balances as $bal) {
            foreach (config('zones.zones', []) as $z) {
                $minOk = $z['min'] === null || $bal >= $z['min'];
                $maxOk = $z['max'] === null || $bal <= $z['max'];
                if ($minOk && $maxOk) {
                    $zoneCounts[$z['name']]['count']++;
                    break;
                }
            }
        }
        // 8 zona selalu tampil (sesuai design doc) walau count 0.
        $counts = array_column($zoneCounts, 'count');
        $maxZoneCount = $counts ? max($counts) : 1;

        // Tren 30 hari: kasus baru vs kasus selesai — 2 query.
        $trendLabels = $trendNew = $trendDone = [];
        $dailyNew = DisciplineCase::where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) AS d, COUNT(*) AS c')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('c', 'd');
        $dailyDone = DisciplineCase::where('status', 'closed')
            ->where('updated_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(updated_at) AS d, COUNT(*) AS c')
            ->groupBy(DB::raw('DATE(updated_at)'))
            ->pluck('c', 'd');

        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $trendLabels[] = now()->subDays($i)->format('d/m');
            $trendNew[] = (int) ($dailyNew[$d] ?? 0);
            $trendDone[] = (int) ($dailyDone[$d] ?? 0);
        }

        // Pelanggaran terbanyak & siswa paling sering melanggar (tahun ajaran aktif, scope wali kelas).
        $statFilters = ['year' => $activeAcademicYear?->id, 'scope' => $this->waliKelasScope()];
        $topCategories = ViolationStats::topCategories($statFilters, 5);
        $topStudents = ViolationStats::topStudents($statFilters, 5);

        return view('dashboard', compact(
            'topCategories',
            'topStudents',
            'totalStudentsActive',
            'totalCasesFound',
            'totalCasesValidated',
            'totalCasesDone',
            'totalCasesDismissed',
            'totalCases',
            'totalCategories',
            'totalCategoriesActive',
            'activeAcademicYear',
            'criticalStudentsCount',
            'lowPointStudents',
            'recentCases',
            'zoneCounts',
            'maxZoneCount',
            'trendLabels',
            'trendNew',
            'trendDone'
        ));
    }

    /** Dashboard khusus siswa murni: saldo, zona, kasus & prestasi milik sendiri. */
    private function studentDashboard($user, ?AcademicYear $activeAcademicYear): View
    {
        $student = $user->student()->with(['currentEnrollment.schoolClass'])->first();

        $balance = $student
            ? \App\Models\PointLedger::getLastBalance($student->id, $activeAcademicYear?->id)
            : (int) config('zones.default_balance', 2000);
        $zone = null;
        foreach (config('zones.zones', []) as $z) {
            $minOk = $z['min'] === null || $balance >= $z['min'];
            $maxOk = $z['max'] === null || $balance <= $z['max'];
            if ($minOk && $maxOk) {
                $zone = $z;
                break;
            }
        }

        $myCases = collect();
        $activeCaseCount = 0;
        $totalCaseYear = 0;
        if ($student) {
            $base = DisciplineCase::where('student_id', $student->id);
            $activeCaseCount = (clone $base)->whereNotIn('status', ['closed', 'dismissed'])->count();
            if ($activeAcademicYear) {
                $totalCaseYear = (clone $base)->where('academic_year_id', $activeAcademicYear->id)->count();
            }
            $myCases = $base->with(['violationCategory', 'schoolClass'])
                ->latest('created_at')
                ->limit(6)
                ->get();
        }

        $myAchievements = $student
            ? \App\Models\AchievementRecord::with('achievementCategory')
                ->where('student_id', $student->id)
                ->latest('created_at')
                ->limit(5)
                ->get()
            : collect();

        $homeroomTeacher = $student?->currentEnrollment?->schoolClass?->homeroomTeacher;
        $recentLedgers = $student
            ? \App\Models\PointLedger::where('student_id', $student->id)
                ->latest('created_at')
                ->limit(5)
                ->get()
            : collect();
        $defaultBalance = (int) config('zones.default_balance', 2000);

        $totalAchievementPoints = $student
            ? (int) \App\Models\AchievementRecord::where('student_id', $student->id)
                ->where('status', 'verified')
                ->when($activeAcademicYear, fn ($q) => $q->where('academic_year_id', $activeAcademicYear->id))
                ->sum('points_awarded')
            : 0;

        if ($balance >= 2500) {
            $nextTargetName = 'Zona Istimewa (Tertinggi)';
            $pointsToNext = 0;
            $visualProgress = 100;
        } elseif ($balance >= 2200) {
            $nextTargetName = 'Zona Istimewa (2.500)';
            $pointsToNext = 2500 - $balance;
            $visualProgress = (int) round((($balance - 2200) / 300) * 100);
        } elseif ($balance >= 2000) {
            $nextTargetName = 'Zona Luar Biasa (2.200)';
            $pointsToNext = 2200 - $balance;
            $visualProgress = (int) round((($balance - 2000) / 200) * 100);
        } else {
            $nextTargetName = 'Standar Sekolah (2.000)';
            $pointsToNext = 2000 - $balance;
            $visualProgress = max(0, min(100, (int) round(($balance / 2000) * 100)));
        }

        return view('dashboard-siswa', compact(
            'student',
            'balance',
            'zone',
            'myCases',
            'activeCaseCount',
            'totalCaseYear',
            'myAchievements',
            'activeAcademicYear',
            'homeroomTeacher',
            'recentLedgers',
            'defaultBalance',
            'totalAchievementPoints',
            'nextTargetName',
            'pointsToNext',
            'visualProgress'
        ));
    }

    /** Dashboard guru/wali kelas: pemantauan rombel binaan — pelanggaran, prestasi, poin. */
    private function teacherDashboard($user, ?AcademicYear $activeAcademicYear): View
    {
        $waliScope = $this->waliKelasScope(); // null = bukan wali kelas, array = id kelas binaan
        $homeroomClasses = collect();
        $students = collect();
        $recentCases = collect();
        $recentAchievements = collect();
        $caseStats = ['total' => 0, 'active' => 0, 'closed' => 0, 'dismissed' => 0];
        $achievementStats = ['verified' => 0, 'pending' => 0];
        $zoneCounts = [];
        foreach (config('zones.zones', []) as $z) {
            $zoneCounts[$z['name']] = ['count' => 0, 'color' => $z['color']];
        }

        if ($waliScope !== null && $waliScope !== []) {
            // Kelas binaan bisa lebih dari satu (homeroom + asisten) — tampilkan semua.
            $homeroomClasses = \App\Models\SchoolClass::with('academicYear')
                ->whereKey($waliScope)
                ->get();

            $studentIds = \App\Models\Student::whereHas(
                'currentEnrollment',
                fn ($q) => $q->whereIn('class_id', $waliScope)->where('status', 'active')
            )->pluck('id');

            $latestBalances = $studentIds->isEmpty() ? collect() : \Illuminate\Support\Facades\DB::table('point_ledgers')
                ->join(DB::raw('(SELECT student_id, MAX(id) AS max_id FROM point_ledgers GROUP BY student_id) lb'),
                    fn ($j) => $j->on('point_ledgers.id', '=', 'lb.max_id'))
                ->whereIn('point_ledgers.student_id', $studentIds)
                ->pluck('point_ledgers.balance_after', 'point_ledgers.student_id');

            $students = $studentIds->isEmpty() ? collect() : \App\Models\Student::whereIn('id', $studentIds)
                ->with(['user', 'currentEnrollment.schoolClass'])->get()
                ->map(function (Student $s) use ($latestBalances) {
                    $s->latest_balance = (int) ($latestBalances[$s->id] ?? (int) config('zones.default_balance', 2000));
                    return $s;
                });

            foreach ($students as $s) {
                foreach (config('zones.zones', []) as $z) {
                    $minOk = $z['min'] === null || $s->latest_balance >= $z['min'];
                    $maxOk = $z['max'] === null || $s->latest_balance <= $z['max'];
                    if ($minOk && $maxOk) {
                        $zoneCounts[$z['name']]['count']++;
                        break;
                    }
                }
            }

            $caseBase = DisciplineCase::whereIn('student_id', $studentIds);
            $caseStats = [
                'total' => (clone $caseBase)->count(),
                'active' => (clone $caseBase)->whereNotIn('status', ['closed', 'dismissed'])->count(),
                'closed' => (clone $caseBase)->where('status', 'closed')->count(),
                'dismissed' => (clone $caseBase)->where('status', 'dismissed')->count(),
            ];
            $recentCases = (clone $caseBase)->with(['violationCategory', 'student.currentEnrollment.schoolClass'])
                ->latest('created_at')->limit(6)->get();

            $achBase = \App\Models\AchievementRecord::whereIn('student_id', $studentIds);
            $achievementStats = [
                'verified' => (clone $achBase)->where('status', 'verified')->count(),
                'pending' => (clone $achBase)->where('status', 'pending')->count(),
            ];
            $recentAchievements = (clone $achBase)->with(['achievementCategory', 'student.currentEnrollment.schoolClass'])
                ->latest('created_at')->limit(5)->get();
        }

        return view('dashboard-guru', compact(
            'user',
            'activeAcademicYear',
            'waliScope',
            'homeroomClasses',
            'students',
            'recentCases',
            'recentAchievements',
            'caseStats',
            'achievementStats',
            'zoneCounts'
        ));
    }

    /** Label range zona untuk tooltip, mis. "1500 - 1599". */
    private function zoneRange(array $zone): string
    {
        if ($zone['min'] === null) {
            return '< '.$zone['max'];
        }
        if ($zone['max'] === null) {
            return '> '.$zone['min'];
        }
        return $zone['min'].' - '.$zone['max'];
    }
}
