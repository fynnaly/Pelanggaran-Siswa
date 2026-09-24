<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\ViolationCategoryController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\PointLedgerController;
use App\Http\Controllers\DisciplineCaseController;
use App\Http\Controllers\AchievementCategoryController;
use App\Http\Controllers\AchievementRecordController;

// Admin/Auth routes
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Auth Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Tahun Ajaran
    Route::prefix('academic-years')->name('tahun-ajaran.')->group(function () {
        Route::get('/', [AcademicYearController::class, 'index'])->name('index');
        Route::get('/create', [AcademicYearController::class, 'create'])->name('create');
        Route::post('/', [AcademicYearController::class, 'store'])->name('store');
        Route::get('/{academicYear}/edit', [AcademicYearController::class, 'edit'])->name('edit');
        Route::put('/{academicYear}', [AcademicYearController::class, 'update'])->name('update');
        Route::delete('/{academicYear}', [AcademicYearController::class, 'destroy'])->name('destroy');
    });

    // Pelanggaran
    Route::resource('violation-categories', ViolationCategoryController::class);

    // Siswa — export/import/template CSV native (tanpa library, PHP 8.5.5 ext-gd kosong)
    // Letak SEBELUM resource agar /students/export tidak kecapture sebagai {student}
      Route::get('students/template', [StudentController::class, 'downloadTemplate'])->name('students.template');
      Route::get('students/export', [StudentController::class, 'export'])->name('students.export');
      Route::post('students/import', [StudentController::class, 'import'])->name('students.import');
      Route::get('students/search', [StudentController::class, 'search'])->name('students.search');
      Route::resource('students', StudentController::class);

    // Kelas — promote HARUS sebelum resource agar tidak tertelan sebagai {class}
    Route::post('classes/promote', [SchoolClassController::class, 'promote'])->name('classes.promote');
    Route::resource('classes', SchoolClassController::class);

    // PointLedger
    Route::resource('point-ledgers', PointLedgerController::class)->only(['index', 'show']);

    // Achievement Category
    Route::resource('achievement-categories', AchievementCategoryController::class);

    // Achievement Record
    Route::resource('achievement-records', AchievementRecordController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('achievement-records/{achievementRecord}/validate', [AchievementRecordController::class, 'validate'])->name('achievement-records.validate');
    Route::patch('achievement-records/{achievementRecord}/done', [AchievementRecordController::class, 'done'])->name('achievement-records.done');
    Route::patch('achievement-records/{achievementRecord}/dismiss', [AchievementRecordController::class, 'dismiss'])->name('achievement-records.dismiss');

    // Kasus Pelanggaran
    Route::prefix('kasus-pelanggaran')->name('kasus-pelanggaran.')->group(function () {
        Route::get('/', [DisciplineCaseController::class, 'index'])->name('index');
        Route::get('/create', [DisciplineCaseController::class, 'create'])->name('create');
        Route::post('/', [DisciplineCaseController::class, 'store'])->name('store');
        Route::get('/{disciplineCase}', [DisciplineCaseController::class, 'show'])->name('show');
        Route::patch('/{disciplineCase}/validate', [DisciplineCaseController::class, 'validate'])->name('validate');
        Route::patch('/{disciplineCase}/dismiss', [DisciplineCaseController::class, 'dismiss'])->name('dismiss');
        Route::patch('/{disciplineCase}/done', [DisciplineCaseController::class, 'done'])->name('done');
    });
});

require __DIR__.'/auth.php';