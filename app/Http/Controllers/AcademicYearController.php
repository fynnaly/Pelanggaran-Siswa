<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AcademicYear;
use App\Services\Academic\PromotionService;
use DB;

class AcademicYearController extends Controller
{
    private const MANAGER_ROLES = ['admin', 'kesiswaan', 'kepala_sekolah'];

    /** Daftar role yang dianggap staf sekolah. User tanpa role apa pun = DITOLAK. */
    private const STAFF_ROLES = ['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'];

    private function authorizeStaff(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(self::STAFF_ROLES),
            403,
            'Akses ditolak. Fitur ini hanya untuk staf sekolah.'
        );
    }

    private function authorizeManagement(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(self::MANAGER_ROLES),
            403,
            'Hanya admin / kesiswaan / kepala sekolah yang boleh mengubah tahun ajaran.',
        );
    }

    /** Display a listing of the resource. */
    public function index(Request $request)
    {
        $this->authorizeStaff();

        // Tahun ajaran yang periodenya sudah berlalu otomatis dinonaktifkan,
        // supaya status is_active tidak bisa "nyangkut" setelah waktunya habis.
        AcademicYear::where('is_active', true)
            ->get()
            ->filter(fn (AcademicYear $year) => $year->isExpired())
            ->each(fn (AcademicYear $year) => $year->update(['is_active' => false]));

        $sort = $request->string('sort')->toString();
        $order = strtolower($request->string('order')->toString()) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'name', 'start_date', 'end_date', 'created_at'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'id';

        $academicYears = AcademicYear::orderBy($sort, $order)->paginate(20)->withQueryString();

        return view('academic-years.index', compact('academicYears', 'sort', 'order'));
    }

    /** Show the form for creating a new resource. */
    public function create()
    {
        $this->authorizeManagement();

        return view('academic-years.create');
    }

    /** Store a newly created resource in storage. */
    public function store(Request $request)
    {
        $this->authorizeManagement();

        // Validasi
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:academic_years,name',
            'start_date' => 'required|date',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle Checkbox
        $validated['is_active'] = $request->has('is_active');

        // Logika Tahun Ajaran Aktif
        if ($validated['is_active']) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }

        // Simpan ke Database
        $newRecord = AcademicYear::create($validated);

        // Jika langsung diaktifkan, jalankan promosi
        if ($validated['is_active']) {
            $result = app(PromotionService::class)->promote($newRecord);

            return redirect()
                ->route('tahun-ajaran.index')
                ->with('success', "Tahun ajaran {$newRecord->name} diaktifkan. {$result['classes_created']} kelas baru, {$result['students_promoted']} siswa naik kelas, {$result['students_graduated']} siswa lulus.");
        }

        // Jika BERHASIL, redirect ke index
        return redirect()
            ->route('tahun-ajaran.index')
            ->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    /** Show the form for editing the specified resource. */
    public function edit(AcademicYear $academicYear)
    {
        $this->authorizeManagement();

        return view('academic-years.edit', compact('academicYear'));
    }

    /** Update the specified resource in storage. */
    public function update(Request $request, AcademicYear $academicYear)
    {
        $this->authorizeManagement();

        // Validasi
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:academic_years,name,' . $academicYear->id,
            'start_date' => 'required|date',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle Checkbox
        $validated['is_active'] = $request->has('is_active');

        // Cek apakah status aktif berubah
        $wasInactive = !$academicYear->is_active;
        $willBeActive = $validated['is_active'];

        // Gunakan transaksi untuk logika aktif tahun ajaran
        DB::transaction(function () use ($validated, $academicYear) {
            // Jika menyetel sebagai aktif, nonaktifkan tahun ajaran lain
            if ($validated['is_active']) {
                AcademicYear::where('is_active', true)
                    ->where('id', '!=', $academicYear->id)
                    ->update(['is_active' => false]);
            }

            // Update data tahun ajaran
            $academicYear->update($validated);
        });

        // Jika baru diaktifkan, jalankan promosi
        if ($wasInactive && $willBeActive) {
            $result = app(PromotionService::class)->promote($academicYear);

            return redirect()
                ->route('tahun-ajaran.index')
                ->with('success', "Tahun ajaran {$academicYear->name} diaktifkan. {$result['classes_created']} kelas baru, {$result['students_promoted']} siswa naik kelas, {$result['students_graduated']} siswa lulus.");
        }

        return redirect()
            ->route('tahun-ajaran.index')
            ->with('success', 'Tahun ajaran berhasil diupdate.');
    }

    /** Aktifkan satu tahun ajaran (nonaktifkan lainnya + jalankan promosi) — tombol "Aktifkan" di index. */
    public function activate(AcademicYear $academicYear)
    {
        $this->authorizeManagement();

        if ($academicYear->is_active) {
            return back()->with('success', "Tahun ajaran {$academicYear->name} sudah aktif.");
        }

        DB::transaction(function () use ($academicYear) {
            // Nonaktifkan tahun ajaran lain, lalu aktifkan yang ini
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
            $academicYear->update(['is_active' => true]);
        });

        $result = app(PromotionService::class)->promote($academicYear);

        return back()
            ->with('success', "Tahun ajaran {$academicYear->name} diaktifkan. {$result['classes_created']} kelas baru, {$result['students_promoted']} siswa naik kelas, {$result['students_graduated']} siswa lulus.");
    }

    /** Remove the specified resource from storage. */
    public function destroy(AcademicYear $academicYear)
    {
        $this->authorizeManagement();

        $academicYear->delete();

        return redirect()
            ->route('tahun-ajaran.index')
            ->with('success', 'Tahun ajaran berhasil dihapus.');
    }
}
