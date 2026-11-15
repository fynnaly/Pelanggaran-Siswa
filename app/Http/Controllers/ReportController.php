<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesWaliKelas;
use App\Models\AcademicYear;
use App\Models\AchievementRecord;
use App\Models\DisciplineCase;
use App\Models\PointLedger;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\ViolationCategory;
use App\Support\StudentDisciplineReport;
use App\Support\ViolationStats;
use App\Support\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    use ScopesWaliKelas;

    /** Daftar role yang dianggap staf sekolah. User tanpa role apa pun = DITOLAK. */
    private const STAFF_ROLES = ['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'];

    private function authorizeStaff(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(self::STAFF_ROLES),
            403,
            'Akses ditolak. Fitur ini hanya untuk staf sekolah.'
        );
    }

    public function index(Request $request): View
    {
        $this->authorizeStaff();

        $filters = $this->filters($request);

        $classes = SchoolClass::orderBy('name')
            ->when($filters['scope'] !== null, fn ($q) => $q->whereIn('id', $filters['scope']))
            ->get(['id', 'name']);

        return view('reports.pelanggaran', [
            'filters' => $filters,
            'years' => AcademicYear::orderByDesc('start_date')->get(['id', 'name']),
            'classes' => $classes,
            'categories' => ViolationStats::topCategories($filters),
            'students' => ViolationStats::topStudents($filters, 20),
            'linkParams' => $this->linkParams($filters),
        ]);
    }

    public function kategori(Request $request, ViolationCategory $violationCategory): View
    {
        $this->authorizeStaff();

        $filters = $this->filters($request);

        $sort = in_array($request->query('sort'), ['tanggal', 'nama', 'kelas'], true) ? $request->query('sort') : 'tanggal';
        $order = $request->query('order') === 'asc' ? 'asc' : 'desc';

        return view('reports.pelanggaran-detail', [
            'category' => $violationCategory->load('parent'),
            'cases' => ViolationStats::categoryCases($violationCategory->id, $filters, $sort, $order),
            'sort' => $sort,
            'order' => $order,
            'linkParams' => $this->linkParams($filters),
        ]);
    }

    /**
     * Show searchable student and class report entry points.
     */
    public function riwayat(Request $request): View
    {
        $this->authorizeStaff();

        $filters = $this->filters($request);
        $scope = $filters['scope'];
        $search = trim($request->string('search')->toString());

        $studentsQuery = Student::query()
            ->with('currentEnrollment.schoolClass')
            ->when($search !== '', fn ($query) => $query->where(function ($studentQuery) use ($search) {
                $studentQuery->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            }));

        if ($scope !== null) {
            $studentsQuery->where(function ($query) use ($scope) {
                $query->whereHas('currentEnrollment', fn ($enrollmentQuery) => $enrollmentQuery->whereIn('class_id', $scope))
                    ->orWhereHas('disciplineCases', fn ($caseQuery) => $caseQuery->whereIn('class_id', $scope))
                    ->orWhereHas('achievementRecords', fn ($achievementQuery) => $achievementQuery->whereIn('class_id', $scope));
            });
        }

        if ($filters['class'] !== null) {
            $studentsQuery->where(function ($query) use ($filters) {
                $query->whereHas('currentEnrollment', fn ($enrollmentQuery) => $enrollmentQuery->where('class_id', $filters['class']))
                    ->orWhereHas('disciplineCases', fn ($caseQuery) => $caseQuery->where('class_id', $filters['class']))
                    ->orWhereHas('achievementRecords', fn ($achievementQuery) => $achievementQuery->where('class_id', $filters['class']));
            });
        }

        $students = $studentsQuery->orderBy('full_name')->paginate(25)->withQueryString();
        $classes = SchoolClass::query()
            ->with('academicYear')
            ->when($scope !== null, fn ($query) => $query->whereIn('id', $scope))
            ->orderBy('name')
            ->get();

        return view('reports.riwayat-index', [
            'filters' => $filters,
            'years' => AcademicYear::orderByDesc('start_date')->get(['id', 'name']),
            'classes' => $classes,
            'students' => $students,
            'search' => $search,
            'linkParams' => $this->linkParams($filters),
        ]);
    }

    /**
     * Display an individual student's discipline history.
     */
    public function studentHistory(Request $request, Student $student, StudentDisciplineReport $report): View
    {
        $this->authorizeStaff();

        $filters = $this->filters($request);
        $this->authorizeStudentReportScope($student, $filters['scope']);
        $this->authorizeSelectedClassScope($filters['class'], $filters['scope']);

        $classes = SchoolClass::query()
            ->when($filters['scope'] !== null, fn ($query) => $query->whereIn('id', $filters['scope']))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('reports.history', [
            'reportType' => 'student',
            'reportTitle' => 'Riwayat Siswa',
            'reportData' => $report->forStudent($student, $filters),
            'student' => $student,
            'schoolClass' => null,
            'filters' => $filters,
            'years' => AcademicYear::orderByDesc('start_date')->get(['id', 'name']),
            'classes' => $classes,
        ]);
    }

    /**
     * Display a class history grouped by each student's snapshot records.
     */
    public function classHistory(Request $request, SchoolClass $schoolClass, StudentDisciplineReport $report): View
    {
        $this->authorizeStaff();

        $filters = $this->filters($request);
        $this->authorizeSelectedClassScope($schoolClass->id, $filters['scope']);
        $filters['class'] = $schoolClass->id;

        return view('reports.history', [
            'reportType' => 'class',
            'reportTitle' => 'Riwayat Kelas',
            'reportData' => $report->forClass($schoolClass, $filters),
            'student' => null,
            'schoolClass' => $schoolClass->load('academicYear'),
            'filters' => $filters,
            'years' => AcademicYear::orderByDesc('start_date')->get(['id', 'name']),
            'classes' => collect([$schoolClass]),
        ]);
    }

    /**
     * Export an individual student's filtered history to XLSX.
     */
    public function exportStudent(Request $request, Student $student, StudentDisciplineReport $report)
    {
        $this->authorizeStaff();

        $filters = $this->filters($request);
        $this->authorizeStudentReportScope($student, $filters['scope']);
        $this->authorizeSelectedClassScope($filters['class'], $filters['scope']);

        $data = $report->forStudent($student, $filters);
        $path = Xlsx::write($this->reportRows($data, $filters['class']), 'Riwayat Siswa', true);

        return response()->download($path, 'riwayat-siswa-'.$student->nis.'-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export a class's filtered history to XLSX.
     */
    public function exportClass(Request $request, SchoolClass $schoolClass, StudentDisciplineReport $report)
    {
        $this->authorizeStaff();

        $filters = $this->filters($request);
        $this->authorizeSelectedClassScope($schoolClass->id, $filters['scope']);
        $filters['class'] = $schoolClass->id;

        $data = $report->forClass($schoolClass, $filters);
        $path = Xlsx::write($this->reportRows($data, $schoolClass->id), 'Riwayat Kelas', true);

        return response()->download($path, 'riwayat-kelas-'.str_replace(' ', '-', $schoolClass->name).'-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Laporan ringkas per kelas: SEMUA kelas ditampilkan sebagai bar chart,
     * termasuk kelas tanpa kasus (bar kosong). Kelas paling sering melanggar
     * diberi penanda. Setiap baris bisa dibuka lanjut ke riwayat kelasnya.
     */
    public function perKelas(Request $request): View
    {
        $this->authorizeStaff();

        $filters = $this->filters($request);

        return view('reports.per-kelas', [
            'filters' => $filters,
            'years' => AcademicYear::orderByDesc('start_date')->get(['id', 'name']),
            'rows' => $this->perClassRows($filters),
            'linkParams' => $this->linkParams($filters),
        ]);
    }

    /**
     * Export XLSX ringkasan per kelas — kolom sama dengan tabel di halaman.
     */
    public function exportPerKelas(Request $request)
    {
        $this->authorizeStaff();

        $filters = $this->filters($request);
        $rows = $this->perClassRows($filters);

        $data = [[
            'No', 'Kelas', 'Tahun Ajaran', 'Wali Kelas', 'Jumlah Siswa',
            'Jumlah Kasus Pelanggaran', 'Jumlah Prestasi',
        ]];
        foreach ($rows as $i => $row) {
            $data[] = [
                $i + 1,
                $row['class']->name,
                $row['class']->academicYear?->name ?? '',
                $row['class']->homeroomTeacher?->name ?? '-',
                $row['students'],
                $row['kasus'],
                $row['prestasi'],
            ];
        }

        // Tidak ada kelas pada filter: tulis pesan eksplisit di file.
        if (count($data) === 1) {
            $data[] = ['Tidak ada data kelas pada filter ini.'];
        }

        $path = Xlsx::write($data, 'Laporan per Kelas', true);

        return response()->download($path, 'laporan-per-kelas-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Gabungkan daftar kelas dengan hitungan kasus + prestasi per kelas.
     * Kelas dengan 0 kasus / 0 prestasi TETAP masuk (nilai 0) supaya chart
     * menampilkan bar kosong, sesuai permintaan.
     */
    private function perClassRows(array $filters): Collection
    {
        $classes = SchoolClass::query()
            ->when($filters['year'], fn ($q) => $q->where('academic_year_id', $filters['year']))
            ->when($filters['scope'] !== null, fn ($q) => $q->whereIn('id', $filters['scope']))
            ->with(['academicYear', 'homeroomTeacher'])
            ->withCount('enrollments')
            ->orderBy('name')
            ->get();

        $caseCounts = ViolationStats::countsPerClass($filters);
        $achievementCounts = $this->achievementCountsPerClass($filters);

        return $classes->map(fn (SchoolClass $class) => [
            'class' => $class,
            'students' => (int) $class->enrollments_count,
            'kasus' => (int) ($caseCounts[$class->id] ?? 0),
            'prestasi' => (int) ($achievementCounts[$class->id] ?? 0),
        ]);
    }

    /**
     * Jumlah rekam prestasi per kelas (status != rejected) dengan rentang
     * tanggal yang sama seperti kasus: event_date, fallback tanggal dibuat.
     */
    private function achievementCountsPerClass(array $f): Collection
    {
        $q = DB::table('achievement_records as ar')
            ->where('ar.status', '<>', 'rejected')
            ->whereNotNull('ar.class_id');

        if (! empty($f['year'])) {
            $q->where('ar.academic_year_id', $f['year']);
        }
        if (! empty($f['from'])) {
            $q->whereRaw('COALESCE(ar.event_date, DATE(ar.created_at)) >= ?', [$f['from']]);
        }
        if (! empty($f['to'])) {
            $q->whereRaw('COALESCE(ar.event_date, DATE(ar.created_at)) <= ?', [$f['to']]);
        }
        if (isset($f['scope'])) {
            $q->whereIn('ar.class_id', $f['scope']);
        }

        return $q->groupBy('ar.class_id')
            ->selectRaw('ar.class_id, COUNT(*) AS total')
            ->pluck('total', 'class_id');
    }

    /**
     * Halaman indeks Laporan Kelas: daftar semua kelas + hitungan kasus & prestasi.
     * Detail & cetak per kelas ada di laporan.kelas (reports/history).
     */
    public function kelasIndex(Request $request): View
    {
        $this->authorizeStaff();

        $filters = $this->filters($request);

        return view('reports.kelas-index', [
            'filters' => $filters,
            'years' => AcademicYear::orderByDesc('start_date')->get(['id', 'name']),
            'rows' => $this->perClassRows($filters),
            'linkParams' => $this->linkParams($filters),
        ]);
    }

    /**
     * Halaman indeks Laporan Siswa: cari siswa + hitungan kasus & prestasi
     * sesuai filter aktif, lalu buka detail riwayatnya.
     */
    public function siswaIndex(Request $request): View
    {
        $this->authorizeStaff();

        $filters = $this->filters($request);
        $scope = $filters['scope'];
        $search = trim($request->string('search')->toString());

        $studentsQuery = Student::query()
            ->with('currentEnrollment.schoolClass')
            ->when($search !== '', fn ($query) => $query->where(function ($studentQuery) use ($search) {
                $studentQuery->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            }));

        if ($scope !== null) {
            $studentsQuery->where(function ($query) use ($scope) {
                $query->whereHas('currentEnrollment', fn ($enrollmentQuery) => $enrollmentQuery->whereIn('class_id', $scope))
                    ->orWhereHas('disciplineCases', fn ($caseQuery) => $caseQuery->whereIn('class_id', $scope))
                    ->orWhereHas('achievementRecords', fn ($achievementQuery) => $achievementQuery->whereIn('class_id', $scope));
            });
        }

        if ($filters['class'] !== null) {
            $studentsQuery->where(function ($query) use ($filters) {
                $query->whereHas('currentEnrollment', fn ($enrollmentQuery) => $enrollmentQuery->where('class_id', $filters['class']))
                    ->orWhereHas('disciplineCases', fn ($caseQuery) => $caseQuery->where('class_id', $filters['class']))
                    ->orWhereHas('achievementRecords', fn ($achievementQuery) => $achievementQuery->where('class_id', $filters['class']));
            });
        }

        // Filter periode yang sama seperti detail laporan: tahun ajaran + rentang tanggal
        // (tanggal kejadian, fallback tanggal dibuat).
        $caseFilter = fn ($q) => $q->where('status', '<>', 'dismissed')
            ->when($filters['year'], fn ($qq) => $qq->where('academic_year_id', $filters['year']))
            ->when($filters['from'], fn ($qq) => $qq->whereRaw('COALESCE(incident_date, DATE(created_at)) >= ?', [$filters['from']]))
            ->when($filters['to'], fn ($qq) => $qq->whereRaw('COALESCE(incident_date, DATE(created_at)) <= ?', [$filters['to']]));

        $achievementFilter = fn ($q) => $q->where('status', '<>', 'rejected')
            ->when($filters['year'], fn ($qq) => $qq->where('academic_year_id', $filters['year']))
            ->when($filters['from'], fn ($qq) => $qq->whereRaw('COALESCE(event_date, DATE(created_at)) >= ?', [$filters['from']]))
            ->when($filters['to'], fn ($qq) => $qq->whereRaw('COALESCE(event_date, DATE(created_at)) <= ?', [$filters['to']]));

        $students = $studentsQuery
            ->withCount([
                'disciplineCases as kasus_count' => $caseFilter,
                'achievementRecords as prestasi_count' => $achievementFilter,
            ])
            ->orderBy('full_name')
            ->paginate(25)
            ->withQueryString();

        $classes = SchoolClass::query()
            ->with('academicYear')
            ->when($scope !== null, fn ($query) => $query->whereIn('id', $scope))
            ->orderBy('name')
            ->get();

        return view('reports.siswa-index', [
            'filters' => $filters,
            'years' => AcademicYear::orderByDesc('start_date')->get(['id', 'name']),
            'classes' => $classes,
            'students' => $students,
            'search' => $search,
            'linkParams' => $this->linkParams($filters),
        ]);
    }

    private function authorizeStudentReportScope(Student $student, ?array $scope): void
    {
        if ($scope === null) {
            return;
        }

        $isInScope = $student->enrollments()->whereIn('class_id', $scope)->exists()
            || $student->disciplineCases()->whereIn('class_id', $scope)->exists()
            || $student->achievementRecords()->whereIn('class_id', $scope)->exists();

        abort_unless($isInScope, 403, 'Akses ditolak. Siswa ini tidak termasuk kelas binaan Anda.');
    }

    private function authorizeSelectedClassScope(?int $classId, ?array $scope): void
    {
        if ($scope !== null && $classId !== null) {
            abort_unless(in_array($classId, $scope, true), 403, 'Akses ditolak. Kelas ini tidak termasuk kelas binaan Anda.');
        }
    }

    /**
     * @param  array{students: Collection<int, array{student: Student, cases: Collection, achievements: Collection, ledger: Collection}>}  $reportData
     * @return array<int, array<int, string>>
     */
    private function reportRows(array $reportData, ?int $classId): array
    {
        $fallbackClassName = $classId !== null ? SchoolClass::whereKey($classId)->value('name') : '';
        $rows = [[
            'NIS', 'NISN', 'Nama Siswa', 'Kelas', 'Tahun Ajaran',
            'Tanggal', 'Jenis Aktivitas', 'Nomor Kasus', 'Kategori', 'Status',
            'Perubahan Poin', 'Catatan', 'Bukti Prestasi',
        ]];

        foreach ($reportData['students'] as $group) {
            $student = $group['student'];
            foreach ($group['cases'] as $case) {
                $rows[] = [
                    $student->nis,
                    $student->nisn ?? '',
                    $student->full_name,
                    $case->schoolClass?->name ?? '',
                    $case->academicYear?->name ?? '',
                    $this->reportDate($case->incident_date, $case->created_at),
                    'Kasus Pelanggaran',
                    $case->case_number,
                    $case->violationCategory?->name ?? '',
                    $case->status,
                    '',
                    $case->description ?? '',
                    '',
                ];
            }

            foreach ($group['achievements'] as $achievement) {
                $rows[] = [
                    $student->nis,
                    $student->nisn ?? '',
                    $student->full_name,
                    $achievement->schoolClass?->name ?? '',
                    $achievement->academicYear?->name ?? '',
                    $this->reportDate($achievement->event_date, $achievement->created_at),
                    'Prestasi',
                    $achievement->disciplineCase?->case_number ?? '',
                    $achievement->achievementCategory?->name ?? '',
                    $achievement->status,
                    '',
                    trim(($achievement->event_name ?? '').' '.($achievement->description ?? '')),
                    $achievement->evidence_path ?? '',
                ];
            }

            foreach ($group['ledger'] as $ledger) {
                $source = $ledger->source;
                $case = $source instanceof DisciplineCase ? $source : null;
                $achievement = $source instanceof AchievementRecord ? $source : null;
                $rows[] = [
                    $student->nis,
                    $student->nisn ?? '',
                    $student->full_name,
                    $case?->schoolClass?->name ?? $achievement?->schoolClass?->name ?? $fallbackClassName,
                    $ledger->academicYear?->name ?? '',
                    $this->reportDate(null, $ledger->created_at),
                    'Transaksi Poin: '.$ledger->transaction_type,
                    $case?->case_number ?? $achievement?->disciplineCase?->case_number ?? '',
                    $case?->violationCategory?->name ?? $achievement?->achievementCategory?->name ?? '',
                    $case?->status ?? $achievement?->status ?? $ledger->transaction_type,
                    ($ledger->direction === PointLedger::DIR_DEBIT ? '-' : '+').(int) $ledger->amount,
                    $ledger->reason ?? '',
                    $achievement?->evidence_path ?? '',
                ];
            }
        }

        // Filter tanpa hasil: tulis pesan eksplisit supaya file XLSX tidak berisi kepala tabel kosong.
        if (count($rows) === 1) {
            $rows[] = ['Tidak ada data pada filter ini.'];
        }

        return $rows;
    }

    private function reportDate(mixed $date, mixed $fallback): string
    {
        return ($date ?? $fallback)?->format('Y-m-d') ?? '';
    }

    private function filters(Request $request): array
    {
        $v = $request->validate([
            'year' => 'nullable|integer|exists:academic_years,id',
            'class' => 'nullable|integer|exists:classes,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        return [
            // param year tidak ada = tahun ajaran aktif; year kosong = semua tahun
            'year' => $request->has('year') ? ($v['year'] ?? null) : AcademicYear::where('is_active', true)->value('id'),
            'class' => $v['class'] ?? null,
            'from' => $v['from'] ?? null,
            'to' => $v['to'] ?? null,
            'scope' => $this->waliKelasScope(),
        ];
    }

    /** Filter yang dibawa link ke halaman detail. year '' dipertahankan agar "Semua" tidak jadi tahun aktif. */
    private function linkParams(array $f): array
    {
        return ['year' => $f['year'] ?? '', 'class' => $f['class'], 'from' => $f['from'], 'to' => $f['to']];
    }
}
