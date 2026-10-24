<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ScopesWaliKelas;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiStudentPointController extends Controller
{
    use ScopesWaliKelas;

    /**
     * Rekap saldo + zona poin (monitor-poin versi JSON untuk Android).
     * Filter identik dengan web: q, class_id, zone, year_id + sort.
     * Scope wali kelas berlaku sama — klien tidak bisa meminta siswa
     * di luar kelas yang diampu, termasuk lewat param class_id.
     */
    public function index(Request $request): JsonResponse
    {
        $q = $request->string('q')->toString();
        $classId = $request->integer('class_id') ?: null;
        $zoneName = $request->string('zone')->toString();
        $selectedYearId = $request->integer('year_id') ?: null;
        $scopeClassIds = $this->waliKelasScope();

        $sort = in_array($request->string('sort')->toString(), ['poin', 'nama', 'kelas', 'zona', 'id'], true)
            ? $request->string('sort')->toString()
            : 'poin';
        $order = strtolower($request->string('order')->toString()) === 'asc' ? 'asc' : 'desc';

        $students = Student::with(['enrollments.schoolClass', 'enrollments.academicYear'])
            ->when($scopeClassIds !== null, fn ($query) => $query
                ->whereHas('currentEnrollment', fn ($qq) => $qq->whereIn('class_id', $scopeClassIds)))
            ->when($selectedYearId, fn ($query) => $query->whereHas('enrollments', fn ($qq) => $qq->where('academic_year_id', $selectedYearId)))
            ->when($classId, fn ($query) => $query->whereHas('enrollments', fn ($qq) => $qq
                ->when($selectedYearId, fn ($s) => $s->where('academic_year_id', $selectedYearId))
                ->where('class_id', $classId)))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('full_name', 'like', "%{$q}%")
                ->orWhere('nisn', 'like', "%{$q}%")
                ->orWhere('nis', 'like', "%{$q}%")))
            ->get();

        if ($zoneName !== '') {
            $students = $students->filter(fn ($s) => $s->zone['name'] === $zoneName);
        }

        $students = $students->sortBy(fn ($s) => match ($sort) {
            'poin' => $s->current_balance,
            'kelas' => $s->class_name,
            'zona' => $s->zone['name'],
            'id' => $s->id,
            default => mb_strtolower($s->full_name),
        }, SORT_REGULAR, $order === 'desc')->values();

        $activeYearId = (int) AcademicYear::where('is_active', true)->value('id');
        $filterYearId = $selectedYearId ?: $activeYearId;
        $classes = SchoolClass::where('academic_year_id', $filterYearId)->orderBy('name')->get();
        if ($scopeClassIds !== null) {
            $classes = $classes->filter(fn ($c) => in_array($c->id, $scopeClassIds, true))->values();
        }

        return response()->json([
            'meta' => [
                'total' => $students->count(),
                'sort' => $sort,
                'order' => $order,
                'scope' => $scopeClassIds === null ? 'semua' : 'wali_kelas',
                'kelas' => $classes->map(fn ($c) => ['id' => $c->id, 'nama' => $c->name]),
            ],
            'data' => $students->map(fn ($s) => [
                'id' => $s->id,
                'nisn' => $s->nisn,
                'nis' => $s->nis,
                'nama' => $s->full_name,
                'kelas' => $s->class_name,
                'tahun_ajaran' => $s->academic_year_name,
                'poin' => $s->current_balance,
                'zona' => $s->zone['name'],
                'penanggung_jawab' => $s->zone['responsible'],
                'tindakan' => $s->zone['action'],
            ]),
        ]);
    }
}
