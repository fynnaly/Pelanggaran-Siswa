<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    private const MANAGER_ROLES = ['admin', 'kesiswaan', 'kepala_sekolah'];

    private function authorizeStaff(): void
    {
        $user = request()->user();
        abort_if(
            ! $user || ($user->hasRole('siswa') && ! $user->hasAnyRole(['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'])),
            403,
            'Akses ditolak. Fitur ini hanya untuk staf sekolah.'
        );
    }

    private function authorizeManagement(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(self::MANAGER_ROLES),
            403,
            'Hanya admin / kesiswaan / kepala sekolah yang boleh mengubah data jurusan.',
        );
    }

    /** Daftar jurusan */
    public function index(Request $request)
    {
        $this->authorizeStaff();

        $q = trim((string) $request->query('q', ''));
        $sort = $request->string('sort')->toString();
        $order = strtolower($request->string('order')->toString()) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'code', 'name', 'status', 'created_at'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'id';

        $departments = Department::query()
            ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            }))
            ->withCount('classes')
            ->orderBy($sort, $order)
            ->paginate(20)
            ->withQueryString();

        return view('departments.index', compact('departments', 'sort', 'order', 'q'));
    }

    public function create()
    {
        $this->authorizeManagement();

        return view('departments.create');
    }

    /** Simpan jurusan baru */
    public function store(Request $request)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:departments,code'],
            'name' => ['required', 'string', 'max:100', 'unique:departments,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        $validated['status'] ??= 'active';

        Department::create($validated);

        return redirect()
            ->route('jurusan.index')
            ->with('success', 'Jurusan ' . $validated['code'] . ' berhasil ditambahkan.');
    }

    public function show(Department $department)
    {
        $this->authorizeStaff();

        $department->load(['classes.academicYear', 'classes.homeroomTeacher']);

        return view('departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        $this->authorizeManagement();

        return view('departments.edit', compact('department'));
    }

    /** Perbarui data jurusan */
    public function update(Request $request, Department $department)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'alpha_dash', \Illuminate\Validation\Rule::unique('departments', 'code')->ignore($department->id)],
            'name' => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::unique('departments', 'name')->ignore($department->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $department->update($validated);

        return redirect()
            ->route('jurusan.index')
            ->with('success', 'Jurusan ' . $department->code . ' berhasil diperbarui.');
    }

    /** Toggle status aktif/nonaktif — menggantikan hapus permanen */
    public function toggleStatus(Department $department)
    {
        $this->authorizeManagement();

        $department->status = $department->status === 'active' ? 'inactive' : 'active';
        $department->save();

        $label = $department->status === 'active' ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', 'Jurusan ' . $department->code . ' berhasil ' . $label . '.');
    }
}
