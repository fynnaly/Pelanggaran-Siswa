<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ViolationCategoryController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\PointLedgerController;
use App\Http\Controllers\DisciplineCaseController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    return view('test-glass-theme');
})->name('test');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    // Auth Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Tahun Ajaran
    Route::resource('academic-years', AcademicYearController::class);

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

    // Kasus Discipline - CRUD dasar + aksi validasi/selesai
    Route::resource('discipline-cases', DisciplineCaseController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('/discipline-cases/{disciplineCase}/validate', [DisciplineCaseController::class, 'validate'])->name('discipline-cases.validate');
    Route::patch('/discipline-cases/{disciplineCase}/done', [DisciplineCaseController::class, 'done'])->name('discipline-cases.done');
});

require __DIR__.'/auth.php';
