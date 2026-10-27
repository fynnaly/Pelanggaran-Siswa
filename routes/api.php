<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiStudentController;
use App\Http\Controllers\Api\ApiStudentPointController;
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
                'GET monitor-poin (auth:sanctum)',
                'GET students/{id}/points (auth:sanctum)',
            ],
        ]);
    });

    Route::post('auth/login', [ApiAuthController::class, 'login'])
        ->middleware('throttle:api-login');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        // Sesi
        Route::get('auth/me', [ApiAuthController::class, 'me']);
        Route::post('auth/logout', [ApiAuthController::class, 'logout']);

        // Monitoring poin — scope wali kelas identik dengan web
        Route::get('monitor-poin', [ApiStudentPointController::class, 'index']);

        // Detail ledger per siswa
        Route::get('students/{student}/points', [ApiStudentController::class, 'points']);
    });
});
