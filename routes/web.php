<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AchievementCategoryController;
use App\Http\Controllers\AchievementRecordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DisciplineCaseController;
use App\Http\Controllers\NfcCardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PointLedgerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPointController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ViolationCategoryController;
use Illuminate\Support\Facades\Route;

// Admin/Auth routes
Route::redirect('/', '/dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'active.account', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'active.account'])->group(function () {
    // Pengaturan global (nama aplikasi, kop sertifikat, tanda tangan digital) —
    // ditaruh SEBELUM resource agar tidak tertelan sebagai {student}/{user}.
    Route::prefix('settings')->name('settings.')
        ->middleware('role:admin|kesiswaan|kepala_sekolah')
        ->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::put('/', [SettingController::class, 'update'])->name('update');
        });

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
        Route::patch('/{academicYear}/activate', [AcademicYearController::class, 'activate'])->name('activate');
        Route::delete('/{academicYear}', [AcademicYearController::class, 'destroy'])->name('destroy');
    });

    // Pelanggaran — rekomendasi pemulihan otomatis per sub-kategori
    Route::get('violation-categories/{violationCategory}/rekomendasi', [ViolationCategoryController::class, 'rekomendasi'])->name('violation-categories.rekomendasi');
    Route::get('violation-categories/template', [ViolationCategoryController::class, 'downloadTemplate'])->name('violation-categories.template');
    Route::get('violation-categories/export', [ViolationCategoryController::class, 'export'])->name('violation-categories.export');
    Route::post('violation-categories/import', [ViolationCategoryController::class, 'import'])->name('violation-categories.import');
    Route::resource('violation-categories', ViolationCategoryController::class);

    // Siswa — export/import/template CSV native (tanpa library, PHP 8.5.5 ext-gd kosong)
    // Letak SEBELUM resource agar /students/export tidak kecapture sebagai {student}
    Route::get('students/template', [StudentController::class, 'downloadTemplate'])->name('students.template');
    Route::get('students/export', [StudentController::class, 'export'])->name('students.export');
    Route::post('students/import', [StudentController::class, 'import'])->name('students.import');
    Route::get('students/search', [StudentController::class, 'search'])->name('students.search');
    Route::resource('students', StudentController::class)->except(['destroy']);

    // Master Guru — template/export/import SEBELUM resource agar tidak tertelan {teacher}
    Route::get('teachers/template', [TeacherController::class, 'downloadTemplate'])->name('teachers.template');
    Route::get('teachers/export', [TeacherController::class, 'export'])->name('teachers.export');
    Route::post('teachers/import', [TeacherController::class, 'import'])->name('teachers.import');
    Route::patch('teachers/{teacher}/toggle-status', [TeacherController::class, 'toggleStatus'])->name('teachers.toggle-status');
    Route::resource('teachers', TeacherController::class)->except(['destroy', 'show']);

    // Nametag NFC (Data Referensi)
    Route::get('nametag-nfc/template', [NfcCardController::class, 'downloadTemplate'])->name('nametag-nfc.template');
    Route::get('nametag-nfc/export', [NfcCardController::class, 'export'])->name('nametag-nfc.export');
    Route::post('nametag-nfc/import', [NfcCardController::class, 'import'])->name('nametag-nfc.import');
    Route::resource('nametag-nfc', NfcCardController::class)->names('nametag-nfc')->parameters(['nametag-nfc' => 'student']);

    // Kelas — promote HARUS sebelum resource agar tidak tertelan sebagai {class}
    Route::post('classes/promote', [SchoolClassController::class, 'promote'])->name('classes.promote');
    Route::resource('classes', SchoolClassController::class);

    // Jurusan / Program Keahlian (tanpa destroy — hanya aktif & nonaktif)
    Route::patch('departments/{department}/toggle-status', [DepartmentController::class, 'toggleStatus'])->name('departments.toggle-status');
    Route::resource('departments', DepartmentController::class)->except(['destroy']);
    Route::patch('jurusan/{department}/toggle-status', [DepartmentController::class, 'toggleStatus'])->name('jurusan.toggle-status');
    Route::resource('jurusan', DepartmentController::class)->except(['destroy'])->names('jurusan')->parameters(['jurusan' => 'department']);

    // PointLedger — siswa/{student} SEBELUM resource agar tidak tertelan {pointLedger}
    Route::get('point-ledgers/siswa/{student}', [PointLedgerController::class, 'forStudent'])->name('point-ledgers.siswa');
    Route::resource('point-ledgers', PointLedgerController::class)->only(['index', 'show']);

    // Monitor Poin — rekap saldo/zona + sertifikat (semua siswa, tanpa batas zona)
    Route::get('/monitor-poin', [StudentPointController::class, 'index'])->name('monitor-poin.index');
    Route::get('/monitor-poin/export', [StudentPointController::class, 'export'])->name('monitor-poin.export');
    Route::post('/monitor-poin/sertifikat/bulk', [StudentPointController::class, 'bulkCertificates'])->name('monitor-poin.bulk-certificates');
    Route::get('/monitor-poin/sertifikat/cetak', [StudentPointController::class, 'printAll'])->name('monitor-poin.print-certificates');
    Route::get('/monitor-poin/{student}/sertifikat', [StudentPointController::class, 'certificate'])->name('monitor-poin.certificate');

    // Achievement Category
    Route::get('achievement-categories/template', [AchievementCategoryController::class, 'downloadTemplate'])->name('achievement-categories.template');
    Route::get('achievement-categories/export', [AchievementCategoryController::class, 'export'])->name('achievement-categories.export');
    Route::post('achievement-categories/import', [AchievementCategoryController::class, 'import'])->name('achievement-categories.import');
    Route::resource('achievement-categories', AchievementCategoryController::class);

    // Achievement Record
    // Kasus terbuka siswa (dropdown tautan penebusan di form create).
    Route::get('students/{student}/open-cases', [AchievementRecordController::class, 'openCases'])
        ->name('achievement-records.open-cases');

    Route::resource('achievement-records', AchievementRecordController::class)
        ->parameters(['achievement-records' => 'achievementRecord'])
        ->only(['index', 'create', 'store', 'show']);
    Route::post('achievement-records/bulk-validate', [AchievementRecordController::class, 'bulkValidate'])->name('achievement-records.bulk-validate');
    Route::patch('achievement-records/{achievementRecord}/validate', [AchievementRecordController::class, 'validate'])->name('achievement-records.validate');
    Route::patch('achievement-records/{achievementRecord}/dismiss', [AchievementRecordController::class, 'dismiss'])->name('achievement-records.dismiss');

    // Kasus Pelanggaran
    Route::prefix('kasus-pelanggaran')->name('kasus-pelanggaran.')->group(function () {
        Route::post('/bulk-status', [DisciplineCaseController::class, 'bulkStatus'])->name('bulk-status');
        Route::get('/', [DisciplineCaseController::class, 'index'])->name('index');
        Route::get('/create', [DisciplineCaseController::class, 'create'])->name('create');
        Route::post('/', [DisciplineCaseController::class, 'store'])->name('store');
        Route::get('/{disciplineCase}', [DisciplineCaseController::class, 'show'])->name('show');
        Route::patch('/{disciplineCase}/update-status', [DisciplineCaseController::class, 'updateStatus'])->name('update-status');
        Route::patch('/{disciplineCase}/dismiss', [DisciplineCaseController::class, 'dismiss'])->name('dismiss');
    });

    // Laporan — statistik pelanggaran terbanyak & detail per kategori
    // Per-kelas didaftarkan sebelum path berparameter agar tidak tertelen pola {param}.
    Route::get('laporan/per-kelas/export', [ReportController::class, 'exportPerKelas'])->name('laporan.per-kelas.export');
    Route::get('laporan/per-kelas', [ReportController::class, 'perKelas'])->name('laporan.per-kelas');
    // URL tanpa ID = halaman indeks laporan (pembeda navigasi kelas vs siswa).
    Route::get('laporan/siswa', [ReportController::class, 'siswaIndex'])->name('laporan.siswa.index');
    Route::get('laporan/kelas', [ReportController::class, 'kelasIndex'])->name('laporan.kelas.index');
    Route::get('laporan/riwayat', [ReportController::class, 'riwayat'])->name('laporan.riwayat');
    Route::get('laporan/siswa/{student}/export', [ReportController::class, 'exportStudent'])->name('laporan.siswa.export');
    Route::get('laporan/siswa/{student}', [ReportController::class, 'studentHistory'])->name('laporan.siswa');
    Route::get('laporan/kelas/{schoolClass}/export', [ReportController::class, 'exportClass'])->name('laporan.kelas.export');
    Route::get('laporan/kelas/{schoolClass}', [ReportController::class, 'classHistory'])->name('laporan.kelas');
    Route::get('laporan/pelanggaran', [ReportController::class, 'index'])->name('laporan.pelanggaran');
    Route::get('laporan/pelanggaran/{violationCategory}', [ReportController::class, 'kategori'])->name('laporan.pelanggaran.kategori');

    // Master Pengguna — template/export/import HARUS sebelum resource agar tidak tertelan {user}
    // + middleware role: pengelola saja (admin/kesiswaan/kepala sekolah).
    Route::middleware('role:admin|kesiswaan|kepala_sekolah')->group(function () {
        Route::get('users/template', [UserController::class, 'downloadTemplate'])->name('users.template');
        Route::get('users/export', [UserController::class, 'export'])->name('users.export');
        Route::post('users/import', [UserController::class, 'import'])->name('users.import');
        Route::patch('users/{user}/deletion-request/approve', [UserController::class, 'approveDeletionRequest'])->name('users.deletion-requests.approve');
        Route::patch('users/{user}/deletion-request/reject', [UserController::class, 'rejectDeletionRequest'])->name('users.deletion-requests.reject');
        Route::patch('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::resource('users', UserController::class);
    });

    // Notifikasi — tandai semua sebagai dibaca
    Route::put('/notifications/mark-read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
});

require __DIR__.'/auth.php';
