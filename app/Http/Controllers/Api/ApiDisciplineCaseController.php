<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\DisciplineCase;
use App\Models\Student;
use App\Models\ViolationCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiDisciplineCaseController extends Controller
{
    /**
     * Pastikan pengguna memiliki peran staf sekolah.
     */
    protected function authorizeStaff(Request $request): ?JsonResponse
    {
        $user = $request->user();
        if ($user && $user->hasRole('siswa') && ! $user->hasAnyRole(['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'])) {
            return response()->json(['message' => 'Akses ditolak. Fitur ini hanya untuk staf sekolah.'], 403);
        }

        return null;
    }

    /**
     * Riwayat kasus pelanggaran yang dilaporkan.
     */
    public function index(Request $request): JsonResponse
    {
        if ($authError = $this->authorizeStaff($request)) {
            return $authError;
        }

        $query = DisciplineCase::query()
            ->with(['student.enrollments.schoolClass', 'schoolClass', 'violationCategory']);

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->integer('student_id'));
        }

        $limit = min(max($request->integer('limit', 15), 1), 100);
        $paginated = $query->orderByDesc('id')->paginate($limit);

        return response()->json([
            'data' => $paginated->getCollection()->map(function (DisciplineCase $case) {
                $violationName = $case->violationCategory?->name ?? '-';
                $points = (int) ($case->points_override ?? $case->violationCategory?->points ?? 0);
                $className = $case->schoolClass?->name ?? $case->student?->class_name ?? '-';

                return [
                    'id' => $case->id,
                    'case_number' => $case->case_number,
                    'student_name' => $case->student?->full_name ?? '-',
                    'class_name' => $className,
                    'violation_name' => $violationName,
                    'points' => $points,
                    'status' => $case->status,
                    'incident_date' => $case->incident_date?->format('Y-m-d') ?? $case->created_at?->format('Y-m-d'),
                    'created_at' => $case->created_at?->toIso8601String(),
                ];
            })->values(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * Kirim laporan pelanggaran baru dari lapangan.
     */
    public function store(Request $request): JsonResponse
    {
        if ($authError = $this->authorizeStaff($request)) {
            return $authError;
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'violation_category_id' => [
                'required',
                'exists:violation_categories,id',
                function ($attribute, $value, $fail) {
                    if (ViolationCategory::whereNull('parent_id')->whereKey($value)->whereHas('children')->exists()) {
                        $fail('Kategori pelanggaran harus sub-kategori, bukan kategori induk.');
                    }
                },
            ],
            'incident_date' => 'nullable|date',
            'incident_time' => 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:100',
            'notes' => 'required|string',
        ], [
            'student_id.required' => 'Siswa wajib dipilih.',
            'student_id.exists' => 'Siswa tidak ditemukan.',
            'violation_category_id.required' => 'Kategori pelanggaran wajib dipilih.',
            'violation_category_id.exists' => 'Kategori pelanggaran tidak ditemukan.',
            'notes.required' => 'Keterangan / catatan pelanggaran wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $student = Student::with(['enrollments.schoolClass'])->findOrFail($validated['student_id']);
        $category = ViolationCategory::findOrFail($validated['violation_category_id']);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $enrollment = $student->enrollments
            ->firstWhere('academic_year_id', $activeYear?->id)
            ?? $student->enrollments->firstWhere('status', 'active')
            ?? $student->enrollments->sortByDesc('id')->first();

        $classId = $enrollment?->class_id;
        $academicYearId = $enrollment?->academic_year_id ?? $activeYear?->id;
        $incidentDate = $validated['incident_date'] ?? now()->toDateString();
        $incidentTime = $validated['incident_time'] ?? now()->format('H:i');

        $case = DB::transaction(function () use (
            $student,
            $enrollment,
            $classId,
            $category,
            $academicYearId,
            $validated,
            $incidentDate,
            $incidentTime,
            $request
        ) {
            return DisciplineCase::create([
                'case_number' => DisciplineCase::generateCaseNumber(),
                'student_id' => $student->id,
                'enrollment_id' => $enrollment?->id,
                'class_id' => $classId,
                'violation_category_id' => $category->id,
                'academic_year_id' => $academicYearId,
                'reporter_id' => $request->user()->id,
                'location' => $validated['location'] ?? null,
                'incident_date' => $incidentDate,
                'incident_time' => $incidentTime,
                'description' => $validated['notes'],
                'status' => 'found',
                'is_recurring' => (bool) $category->is_recurring,
            ]);
        });

        $points = (int) $category->points;

        return response()->json([
            'success' => true,
            'message' => 'Laporan pelanggaran berhasil dicatat (status: Temuan).',
            'data' => [
                'case_number' => $case->case_number,
                'student_name' => $student->full_name,
                'violation' => $category->name,
                'points' => $points,
                'status' => $case->status,
                'reporter' => $request->user()->name,
            ],
        ], 201);
    }
}
