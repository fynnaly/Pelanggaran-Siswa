<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\DisciplineCase;
use App\Models\Student;
use App\Models\ViolationCategory;
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

        // Siswa dengan sisa poin rendah (<= 500) berdasarkan balance_after ledger terakhir.
        // Saldo dihitung di controller lalu disimpan ke atribut dinamis $student->latest_balance
        // agar view hanya merender tanpa query inline (menghindari N+1 di Blade).
        $lowPointStudents = Student::query()
            ->where('status', 'active')
            ->whereHas('pointLedgers')
            ->with(['user', 'schoolClass'])
            ->get()
            ->map(function (Student $student) {
                $student->latest_balance = $student->pointLedgers()->latest('id')->value('balance_after');
                return $student;
            })
            ->where('latest_balance', '<=', 500)
            ->take(5)
            ->values();

        $recentCases = DisciplineCase::with(['student', 'violationCategory', 'reporter'])
            ->latest('created_at')
            ->limit(8)
            ->get();

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
            'recentCases'
        ));
    }
}