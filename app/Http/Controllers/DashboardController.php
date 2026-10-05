<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\DisciplineCase;
use App\Models\Student;
use App\Models\ViolationCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalStudentsActive = Student::where('status', 'active')->count();

        $totalCasesFound = DisciplineCase::where('status', 'found')->count();
        $totalCasesValidated = DisciplineCase::where('status', 'validated')->count();
        $totalCasesDone = DisciplineCase::where('status', 'done')->count();
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

        $recentCases = DisciplineCase::with(['student', 'violationCategory', 'reporter'])
            ->latest('created_at')
            ->limit(8)
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
        $dailyDone = DisciplineCase::where('status', 'done')
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

        return view('dashboard', compact(
            'totalStudentsActive',
            'totalCasesFound',
            'totalCasesValidated',
            'totalCasesDone',
            'totalCasesDismissed',
            'totalCases',
            'totalCategories',
            'totalCategoriesActive',
            'activeAcademicYear',
            'lowPointStudents',
            'recentCases',
            'zoneCounts',
            'maxZoneCount',
            'trendLabels',
            'trendNew',
            'trendDone'
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
