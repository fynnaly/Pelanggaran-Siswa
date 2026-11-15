<?php

use App\Http\Controllers\Api\ApiAchievementController;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiDisciplineCaseController;
use App\Http\Controllers\Api\ApiProfileController;
use App\Http\Controllers\Api\ApiReportController;
use App\Http\Controllers\Api\ApiStudentController;
use App\Http\Controllers\Api\ApiStudentPointController;
use App\Http\Controllers\Api\ApiViolationCategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Android)
|--------------------------------------------------------------------------
|
| Prefix /api otomatis dari bootstrap/app.php. Semua endpoint memakai
| token Bearer (Sanctum) kecuali login. Throttle dibedakan:
|   api-login — 5 menit/menit per email|ip  (anti brute force)
|   api       — 60 menit/menit per user      (anti abuse)
|
*/

Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'name' => config('app.name', 'Pelanggaran Kuy'),
            'version' => 'v1',
            'endpoints' => [
                'POST auth/login',
                'GET auth/me (auth:sanctum)',
                'POST auth/logout (auth:sanctum)',
                'PATCH auth/password (auth:sanctum)',
                'GET dashboard (auth:sanctum)',
                'GET profile (auth:sanctum)',
                'PATCH profile (auth:sanctum)',
                'GET monitor-poin (auth:sanctum)',
                'GET students/{id}/points (auth:sanctum)',
                'GET students/search (auth:sanctum)',
                'GET students/by-nfc/{uid} (auth:sanctum)',
                'GET violation-categories (auth:sanctum)',
                'GET discipline-cases (auth:sanctum)',
                'POST discipline-cases (auth:sanctum)',
                'GET achievement-categories (auth:sanctum)',
                'GET students/{student}/achievements (auth:sanctum)',
                'POST achievement-records (auth:sanctum)',
                'GET reports/students/{student} (auth:sanctum)',
                'GET reports/classes/{schoolClass} (auth:sanctum)',
            ],
        ]);
    });

    Route::post('auth/login', [ApiAuthController::class, 'login'])
        ->middleware('throttle:api-login');

    Route::middleware(['auth:sanctum', 'active.account', 'throttle:api'])->group(function () {
        // Sesi
        Route::get('auth/me', [ApiAuthController::class, 'me']);
        Route::post('auth/logout', [ApiAuthController::class, 'logout']);
        // Ganti password sendiri (semua role) — wajib password lama
        Route::patch('auth/password', [ApiAuthController::class, 'changePassword']);

        // Ringkasan beranda per role (staf: statistik sekolah, siswa: poin sendiri)
        Route::get('dashboard', [ApiDashboardController::class, 'index']);

        // Profil pribadi dan pengelolaan avatar
        Route::get('profile', [ApiProfileController::class, 'show']);
        Route::patch('profile', [ApiProfileController::class, 'update']);

        // Monitoring poin — scope wali kelas identik dengan web
        Route::get('monitor-poin', [ApiStudentPointController::class, 'index']);

        // Detail ledger per siswa
        Route::get('students/{student}/points', [ApiStudentController::class, 'points']);

        // Input Pelanggaran, Pencarian & NFC
        Route::get('students/search', [ApiStudentController::class, 'search']);
        Route::get('students/by-nfc/{uid}', [ApiStudentController::class, 'byNfc']);
        Route::get('violation-categories', [ApiViolationCategoryController::class, 'index']);
        Route::get('discipline-cases', [ApiDisciplineCaseController::class, 'index']);
        Route::post('discipline-cases', [ApiDisciplineCaseController::class, 'store']);

        // Prestasi siswa
        Route::get('achievement-categories', [ApiAchievementController::class, 'categories']);
        Route::get('students/{student}/achievements', [ApiAchievementController::class, 'index']);
        Route::post('achievement-records', [ApiAchievementController::class, 'store']);

        // Rekap laporan dengan scope yang sama dengan web
        Route::get('reports/students/{student}', [ApiReportController::class, 'student']);
        Route::get('reports/classes/{schoolClass}', [ApiReportController::class, 'schoolClass']);
    });
});
