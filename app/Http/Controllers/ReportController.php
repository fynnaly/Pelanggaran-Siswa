<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesWaliKelas;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\ViolationCategory;
use App\Support\ViolationStats;
use Illuminate\Http\Request;
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
