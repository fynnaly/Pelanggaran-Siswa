<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Pagination\LengthAwarePaginator;

class StudentPointController extends Controller
{
    public function index()
    {
        [$students] = $this->filteredStudents();

        $sort = request('sort', 'poin');
        $order = request('order', 'desc');
        $allowedSort = ['poin', 'nama', 'kelas', 'zona', 'id'];
        $sort = in_array($sort, $allowedSort, true) ? $sort : 'poin';
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

        $students = $this->sortStudents($students, $sort, $order);

        $perPage = 20;
        $page = max(1, (int) request('page', 1));
        $paginated = new LengthAwarePaginator(
            $students->forPage($page, $perPage)->values(),
            $students->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        [$allClasses, $academicYears, $selectedYearId, $filterYearId] = $this->dropdownData();

        return view('monitor-poin.index', compact('paginated', 'allClasses', 'sort', 'order', 'academicYears', 'selectedYearId', 'filterYearId'));
    }

    /**
     * Export CSV native — ikut pola students/users (tanpa library, ext-gd kills maatwebsite).
     * Filter aktif (q/kelas/zona/tahun) ikut diterapkan, semua baris tanpa paginasi.
     */
    public function export()
    {
        [$students] = $this->filteredStudents();
        $students = $this->sortStudents($students, 'poin', 'desc');

        $filename = 'monitor-poin-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            // BOM agar Excel Windows baca UTF-8 dengan benar
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['No', 'NISN', 'NIS', 'Nama', 'Kelas', 'Tahun Ajaran', 'Saldo Poin', 'Zona Pembinaan'], ',', '"', '\\');

            foreach ($students as $i => $s) {
                fputcsv($out, [
                    $i + 1,
                    $s->nisn,
                    $s->nis,
                    $s->full_name,
                    $s->class_name,
                    $s->academic_year_name,
                    $s->current_balance,
                    $s->zone['name'],
                ], ',', '"', '\\');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Query + filter (q, kelas, zona, tahun ajaran) — dipakai index & export. */
    private function filteredStudents(): array
    {
        $q = request('q');
        $classId = request('class_id');
        $zoneName = request('zone');
        $selectedYearId = request('year_id') ? (int) request('year_id') : null;

        $students = Student::with(['enrollments.schoolClass', 'enrollments.academicYear'])
            ->when($selectedYearId, fn ($query) => $query->whereHas('enrollments', fn ($qq) => $qq->where('academic_year_id', $selectedYearId)))
            ->when($classId, fn ($query) => $query->whereHas('enrollments', fn ($qq) => $qq->when($selectedYearId, fn ($s) => $s->where('academic_year_id', $selectedYearId))->where('class_id', $classId)))
            ->when($q, fn ($query) => $query->where(fn ($w) => $w->where('full_name', 'like', "%{$q}%")->orWhere('nisn', 'like', "%{$q}%")->orWhere('nis', 'like', "%{$q}%")))
            ->get();

        if ($zoneName) {
            $students = $students->filter(fn ($s) => $s->zone['name'] === $zoneName)->values();
        }

        return [$students, $selectedYearId];
    }

    private function sortStudents($students, string $sort, string $order)
    {
        return $students->sortBy(fn ($s) => match ($sort) {
            'poin' => $s->current_balance,
            'kelas' => $s->class_name,
            'zona' => $s->zone['name'],
            'id' => $s->id,
            default => mb_strtolower($s->full_name),
        }, SORT_REGULAR, $order === 'desc')->values();
    }

    /** Data dropdown filter (kelas + tahun ajaran) — dipakai index. */
    private function dropdownData(): array
    {
        $activeYearId = (int) AcademicYear::where('is_active', true)->value('id');
        $academicYears = AcademicYear::orderBy('start_date')->get();
        $selectedYearId = request('year_id') ? (int) request('year_id') : null;
        $filterYearId = $selectedYearId ?: $activeYearId;
        if (! $selectedYearId && SchoolClass::where('academic_year_id', $filterYearId)->count() === 0) {
            $filterYearId = (int) (SchoolClass::query()->select('academic_year_id')->distinct()->orderByDesc('academic_year_id')->value('academic_year_id') ?? $activeYearId);
        }
        $allClasses = SchoolClass::where('academic_year_id', $filterYearId)->orderBy('name')->get();

        return [$allClasses, $academicYears, $selectedYearId, $filterYearId];
    }

    public function certificate(Student $student)
    {
        $zone = $student->zone;
        abort_unless(in_array($zone['name'], ['Istimewa', 'Luar Biasa'], true), 403, 'Sertifikat hanya untuk zona Istimewa / Luar Biasa.');
        return view('monitor-poin.certificate', compact('student', 'zone'));
    }
}
