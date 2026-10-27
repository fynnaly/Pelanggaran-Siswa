<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesWaliKelas;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use ZipArchive;

class StudentPointController extends Controller
{
    use ScopesWaliKelas;

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

    /** Query + filter (q, kelas, zona, tahun ajaran) — dipakai index & export.
     *  Scope RBAC wali kelas berasal dari trait ScopesWaliKelas (sumber tunggal
     *  bersama API) supaya web dan API tidak berpisah aturannya.
     */
    private function filteredStudents(): array
    {
        $q = request('q');
        $classId = request('class_id');
        $zoneName = request('zone');
        $selectedYearId = request('year_id') ? (int) request('year_id') : null;
        $scopeClassIds = $this->waliKelasScope();

        $students = Student::with(['enrollments.schoolClass', 'enrollments.academicYear'])
            ->when($scopeClassIds !== null, fn ($query) => $query
                ->whereHas('currentEnrollment', fn ($qq) => $qq->whereIn('class_id', $scopeClassIds)))
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

        // Wali kelas: dropdown hanya kelas yang diampu (konsisten dengan scope query).
        $scope = $this->waliKelasScope();
        if ($scope !== null) {
            $allClasses = $allClasses->filter(fn ($c) => in_array($c->id, $scope, true))->values();
        }

        return [$allClasses, $academicYears, $selectedYearId, $filterYearId];
    }

    /**
     * Sertifikat per siswa — TANPA batas zona (semua siswa boleh dicetak).
     * Tetap di-scope wali kelas: tidak boleh buka sertifikat siswa di luar kelas ampu.
     */
    public function certificate(Student $student)
    {
        $this->authorizeStudent($student);

        $zone = $student->zone;
        return view('monitor-poin.certificate', compact('student', 'zone'));
    }

    /**
     * Cetak sertifikat semua siswa terpilih dalam SATU halaman (page-break per
     * siswa) → dialog cetak "Save as PDF" menghasilkan PDF multi-halaman.
     * Cara paling praktis untuk operator: pilih → cetak → PDF, tanpa membuka
     * file ZIP satu per satu.
     */
    public function printAll(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:students,id',
        ]);

        $students = $this->studentsForBulk($validated['ids']);

        abort_if($students->isEmpty(), 422, 'Tidak ada siswa yang cocok untuk sertifikat.');

        return view('monitor-poin.bulk-print', compact('students'));
    }

    /**
     * Bulk sertifikat → 1 arsip ZIP berisi HTML per siswa.
     *
     * Nama file per siswa: Sertifikat-Poin_{nama}_{kelas}_{tahun-ajaran}.html
     * contoh: Sertifikat-Poin_Udin-Saepul_XII-PPLG-1_2026.html
     *
     * ZIP dipilih atas multi-download: Chrome memblokir >10 unduhan otomatis
     * per origin, sedangkan kelas bisa berisi puluhan siswa.
     */
    public function bulkCertificates(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:students,id',
        ]);

        $students = $this->studentsForBulk($validated['ids']);

        abort_if($students->isEmpty(), 422, 'Tidak ada siswa yang cocok untuk sertifikat.');

        $slug = fn (string $t): string => trim(preg_replace('/-+/', '-', preg_replace('/[^A-Za-z0-9]+/', '-', $t)), '-');

        $zipPath = storage_path('app/sertifikat-' . now()->format('Ymd-His') . '.zip');
        $zip = new ZipArchive;
        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Gagal membuat arsip.');

        foreach ($students as $s) {
            $html = view('monitor-poin.certificate', ['student' => $s, 'zone' => $s->zone])->render();
            $nama = $slug($s->full_name);
            $kelas = $slug($s->class_name);
            $tahun = $slug($s->academic_year_name);
            $zip->addFromString("Sertifikat-Poin_{$nama}_{$kelas}_{$tahun}.html", $html);
        }
        $zip->close();

        $zipName = 'Sertifikat-Poin-' . now()->format('Ymd-His') . '.zip';

        return response()->download($zipPath, $zipName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /** Ambil siswa terpilih untuk bulk sertifikat — +scope RBAC wali kelas. */
    private function studentsForBulk(array $ids)
    {
        $scope = $this->waliKelasScope();

        return Student::with(['enrollments.schoolClass', 'enrollments.academicYear'])
            ->when($scope !== null, fn ($q) => $q
                ->whereHas('enrollments', fn ($qq) => $qq->whereIn('class_id', $scope)))
            ->whereIn('id', $ids)
            ->orderBy('full_name')
            ->get();
    }

    /** Cegah IDOR: wali kelas hanya boleh menyentuh siswa kelas ampu sendiri. */
    private function authorizeStudent(Student $student): void
    {
        $scope = $this->waliKelasScope();
        if ($scope === null) {
            return;
        }

        $allowed = $student->enrollments()
            ->whereIn('class_id', $scope)
            ->exists();

        abort_unless($allowed, 403, 'Siswa di luar kelas yang Anda ampu.');
    }
}
