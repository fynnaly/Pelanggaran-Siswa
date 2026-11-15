<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ScopesWaliKelas;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AchievementRecord;
use App\Models\DisciplineCase;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ringkasan beranda (dashboard) per role — satu endpoint untuk layar utama
 * aplikasi mobile: staf melihat statistik sekolah, siswa melihat poin sendiri.
 */
class ApiDashboardController extends Controller
{
    use ScopesWaliKelas;

    private const STAFF_ROLES = ['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isStaff = $user?->hasAnyRole(self::STAFF_ROLES) ?? false;

        return $isStaff ? $this->staffDashboard($request, $user) : $this->studentDashboard($user);
    }

    /** Staf: statistik kasus/prestasi tahun ajaran aktif + daftar kasus terbaru. */
    private function staffDashboard(Request $request, $user): JsonResponse
    {
        $scopeClassIds = $this->waliKelasScope();
        $activeYearId = AcademicYear::where('is_active', true)->value('id');

        $cases = DisciplineCase::query()
            ->when($activeYearId, fn ($q) => $q->where('academic_year_id', $activeYearId))
            ->when($scopeClassIds !== null, fn ($q) => $q->whereIn('class_id', $scopeClassIds));
        $achievements = AchievementRecord::query()
            ->where('status', '<>', 'rejected')
            ->when($activeYearId, fn ($q) => $q->where('academic_year_id', $activeYearId))
            ->when($scopeClassIds !== null, fn ($q) => $q->whereIn('class_id', $scopeClassIds));

        $stats = [
            // Semua kasus yang masih diproses (belum selesai / dibuang).
            'kasus_aktif' => (clone $cases)->whereNotIn('status', ['closed', 'dismissed'])->count(),
            // Kasus + prestasi yang muncul pada bulan kalender berjalan.
            'kasus_bulan_ini' => (clone $cases)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'prestasi_bulan_ini' => (clone $achievements)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'total_siswa' => Student::query()
                ->when($scopeClassIds !== null, fn ($q) => $q->whereHas('currentEnrollment', fn ($qq) => $qq->whereIn('class_id', $scopeClassIds)))
                ->count(),
        ];

        $recentCases = (clone $cases)
            ->with(['student', 'violationCategory', 'schoolClass'])
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (DisciplineCase $case) => [
                'case_number' => $case->case_number,
                'student_name' => $case->student?->full_name ?? '-',
                'class_name' => $case->schoolClass?->name ?? '-',
                'violation_name' => $case->violationCategory?->name ?? '-',
                'points' => (int) ($case->points_override ?? $case->violationCategory?->points ?? 0),
                'status' => $case->status,
                'incident_date' => $case->incident_date?->format('Y-m-d') ?? $case->created_at?->format('Y-m-d'),
            ]);

        return response()->json([
            'role' => 'staf',
            'scope' => $scopeClassIds === null ? 'semua' : 'wali_kelas',
            'tahun_ajaran_aktif' => $activeYearId ? AcademicYear::find($activeYearId)?->name : null,
            'stats' => $stats,
            'kasus_terbaru' => $recentCases,
        ]);
    }

    /** Siswa: poin sendiri + rekap aktivitas + riwayat terbaru. */
    private function studentDashboard($user): JsonResponse
    {
        $student = $user->student;
        if (! $student) {
            return response()->json(['message' => 'Akun siswa Anda belum terhubung dengan data siswa.'], 403);
        }

        $student->load(['currentEnrollment.schoolClass']);

        $casesQuery = DisciplineCase::where('student_id', $student->id)->where('status', '<>', 'dismissed');
        $achievementsQuery = AchievementRecord::where('student_id', $student->id)->where('status', '<>', 'rejected');

        $recentCases = (clone $casesQuery)
            ->with(['violationCategory', 'schoolClass'])
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (DisciplineCase $case) => [
                'case_number' => $case->case_number,
                'violation_name' => $case->violationCategory?->name ?? '-',
                'points' => (int) ($case->points_override ?? $case->violationCategory?->points ?? 0),
                'status' => $case->status,
                'incident_date' => $case->incident_date?->format('Y-m-d') ?? $case->created_at?->format('Y-m-d'),
            ]);

        $recentAchievements = (clone $achievementsQuery)
            ->with('achievementCategory')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (AchievementRecord $record) => [
                'event_name' => $record->event_name ?: ($record->achievementCategory?->name ?? '-'),
                'category_name' => $record->achievementCategory?->name ?? '-',
                'points' => (int) $record->points_awarded,
                'status' => $record->status,
                'event_date' => $record->event_date?->format('Y-m-d') ?? $record->created_at?->format('Y-m-d'),
            ]);

        return response()->json([
            'role' => 'siswa',
            'siswa' => [
                'id' => $student->id,
                'nama' => $student->full_name,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
                'kelas' => $student->currentEnrollment?->schoolClass?->name,
            ],
            'stats' => [
                'poin' => (int) $student->current_balance,
                'zona' => $student->zone['name'],
                'penanggung_jawab' => $student->zone['responsible'],
                'tindakan' => $student->zone['action'],
                'kasus' => (clone $casesQuery)->count(),
                'prestasi' => (clone $achievementsQuery)->count(),
            ],
            'kasus_terbaru' => $recentCases,
            'prestasi_terbaru' => $recentAchievements,
        ]);
    }
}
