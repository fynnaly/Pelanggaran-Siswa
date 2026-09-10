<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ViolationCategoryController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\PointLedgerController;
use App\Http\Controllers\DisciplineCaseController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    return view('test-glass-theme');
})->name('test');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Auth Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Pelanggaran
    Route::resource('violation-categories', ViolationCategoryController::class);

    // Siswa / Murid
    Route::resource('students', StudentController::class);

    // PointLedger
    Route::resource('point-ledgers', PointLedgerController::class)->only(['index', 'show']);

    // Kasus Validasi / Selesai
    Route::patch('/discipline-cases/{disciplineCase}/validate', [DisciplineCaseController::class, 'validate'])->name('discipline-cases.validate');
    Route::patch('/discipline-cases/{disciplineCase}/done', [DisciplineCaseController::class, 'done'])->name('discipline-cases.done');
});


require __DIR__.'/auth.php';
