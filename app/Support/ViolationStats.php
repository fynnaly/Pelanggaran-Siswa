<?php

namespace App\Support;

use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Statistik pelanggaran dari jumlah KASUS (bukan poin). Kasus `dismissed` tidak dihitung.
 *
 * $filters: year, class, from, to (semua opsional) + scope.
 * scope: null = tanpa batas, array id kelas = batasi, [] = hasil KOSONG (bukan tanpa filter).
 */
class ViolationStats
{
    /** Tanggal kejadian; incident_date nullable jadi fallback ke tanggal dibuat. */
    private const DATE = 'COALESCE(dc.incident_date, DATE(dc.created_at))';

    private static function base(array $f): Builder
    {
        $q = DB::table('discipline_cases as dc')->where('dc.status', '<>', 'dismissed');

        if (! empty($f['year'])) {
            $q->where('dc.academic_year_id', $f['year']);
        }
        if (! empty($f['class'])) {
            $q->where('dc.class_id', $f['class']);
        }
        if (isset($f['scope'])) {
            $q->whereIn('dc.class_id', $f['scope']); // [] => 0 = 1, kosong
        }
        if (! empty($f['from'])) {
            $q->whereRaw(self::DATE.' >= ?', [$f['from']]);
        }
        if (! empty($f['to'])) {
            $q->whereRaw(self::DATE.' <= ?', [$f['to']]);
        }

        return $q;
    }

    public static function topCategories(array $f, ?int $limit = null): Collection
    {
        return self::base($f)
            ->join('violation_categories as vc', 'vc.id', '=', 'dc.violation_category_id')
            ->leftJoin('violation_categories as p', 'p.id', '=', 'vc.parent_id')
            ->groupBy('vc.id', 'vc.code', 'vc.name', 'vc.severity', 'p.name')
            ->selectRaw('vc.id, vc.code, vc.name, vc.severity, p.name AS parent_name, COUNT(*) AS total, COUNT(DISTINCT dc.student_id) AS students')
            ->orderByDesc('total')->orderBy('vc.name')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();
    }

    public static function topStudents(array $f, ?int $limit = null): Collection
    {
        $rows = self::base($f)
            ->join('students as s', 's.id', '=', 'dc.student_id')
            ->groupBy('s.id', 's.full_name', 's.nisn')
            ->selectRaw('s.id, s.full_name, s.nisn, COUNT(*) AS total, MAX('.self::DATE.') AS last_date')
            ->orderByDesc('total')->orderBy('s.full_name')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();

        $students = Student::with('currentEnrollment.schoolClass')
            ->whereIn('id', $rows->pluck('id'))->get()->keyBy('id');

        return $rows->each(fn ($r) => $r->class_name = $students[$r->id]->currentEnrollment?->schoolClass?->name);
    }

    /** Daftar kasus satu kategori. $sort: tanggal|nama|kelas (whitelist). */
    public static function categoryCases(int $categoryId, array $f, string $sort, string $order): LengthAwarePaginator
    {
        $col = ['tanggal' => self::DATE, 'nama' => 's.full_name', 'kelas' => 'c.name'][$sort] ?? self::DATE;
        $dir = $order === 'asc' ? 'asc' : 'desc';

        return self::base($f)
            ->where('dc.violation_category_id', $categoryId)
            ->join('students as s', 's.id', '=', 'dc.student_id')
            ->leftJoin('classes as c', 'c.id', '=', 'dc.class_id')
            ->selectRaw('dc.id, dc.case_number, dc.status, s.id AS student_id, s.full_name, c.name AS class_name, '.self::DATE.' AS tanggal')
            ->orderByRaw($col.' '.$dir)->orderByDesc('dc.id')
            ->paginate(20)->withQueryString();
    }
}
