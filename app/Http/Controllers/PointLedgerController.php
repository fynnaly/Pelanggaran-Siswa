<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesWaliKelas;
use App\Models\AchievementRecord;
use App\Models\PointLedger;
use App\Models\Student;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

class PointLedgerController extends Controller
{
    use ScopesWaliKelas;

    /** Daftar role yang dianggap staf sekolah. */
    private const STAFF_ROLES = ['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasAnyRole(array_merge(self::STAFF_ROLES, ['siswa'])),
            403,
            'Akses ditolak. Fitur ini hanya untuk pengguna terdaftar.'
        );

        $isSiswa = $user?->hasRole('siswa') && ! $user->hasAnyRole(self::STAFF_ROLES);

        // Jika siswa membuka riwayat poin, redirect langsung ke riwayat miliknya sendiri
        if ($isSiswa) {
            if ($user->student) {
                return redirect()->route('point-ledgers.siswa', $user->student);
            }
            abort(403, 'Akses ditolak. Akun siswa Anda belum terhubung dengan data siswa.');
        }

        $search = $request->string('search')->trim();
        $direction = $request->string('direction')->toString();
        $type = $request->string('type')->toString();
        $sort = $request->string('sort')->toString();
        $order = strtolower($request->string('order')->toString()) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'created_at', 'amount', 'balance_after', 'direction', 'transaction_type'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'created_at';

        $query = PointLedger::with(['student.currentEnrollment.schoolClass', 'academicYear']);

        // Scope wali kelas: hanya riwayat poin siswa di kelas binaan
        $waliScope = $this->waliKelasScope();
        if ($waliScope !== null) {
            $query->whereHas(
                'student.currentEnrollment',
                fn ($eq) => $eq->whereIn('class_id', $waliScope)
            );
        }

        $query->when($search, fn ($q, $s) => $q->whereHas('student', fn ($sq) => $sq->where('full_name', 'like', "%{$s}%")
            ->orWhere('nisn', 'like', "%{$s}%")
        ))
            ->when($direction, fn ($q, $d) => $q->where('direction', $d))
            ->when($type, fn ($q, $t) => $q->where('transaction_type', $t));

        $ledgers = $sort === 'created_at'
            ? $query->latest()->paginate(20)->withQueryString()
            : $query->orderBy($sort, $order)->paginate(20)->withQueryString();

        return view('point-ledgers.index', compact(
            'ledgers', 'search', 'direction', 'type', 'sort', 'order'
        ));
    }

    /**
     * Riwayat poin satu siswa — dipakai tombol "Detail" di Monitor Poin.
     *
     * Monitor Poin menampilkan baris per SISWA, sedangkan show() butuh id
     * ledger tunggal. Method ini menjadi jembatannya: tampilkan seluruh
     * ledger siswa (tahun ajaran terakhir yang punya transaksi) dengan
     * tampilan yang sama persis.
     */
    public function forStudent(Student $student)
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasAnyRole(array_merge(self::STAFF_ROLES, ['siswa'])),
            403,
            'Akses ditolak. Fitur ini hanya untuk pengguna terdaftar.'
        );

        if ($user && $user->hasRole('siswa') && ! $user->hasAnyRole(self::STAFF_ROLES)) {
            $myStudentId = $user->student?->id;
            abort_if(! $myStudentId || $student->id !== $myStudentId, 403, 'Akses ditolak. Anda hanya boleh melihat buku saku poin Anda sendiri.');
        }

        // Scope wali kelas: hanya siswa di kelas binaan
        $waliScope = $this->waliKelasScope();
        if ($waliScope !== null) {
            $studentClassId = $student->currentEnrollment?->class_id;
            abort_if(
                ! $studentClassId || ! in_array($studentClassId, $waliScope, true),
                403,
                'Akses ditolak. Siswa tersebut bukan anggota kelas yang Anda bimbing.'
            );
        }

        // Ambil tahun ajaran terakhir yang punya transaksi untuk siswa ini,
        // supaya kolom "Saldo" (balance_after) tetap bermakna (per-tahun).
        $yearId = PointLedger::where('student_id', $student->id)
            ->latest('id')
            ->value('academic_year_id');

        $student->load(['user', 'currentEnrollment.schoolClass']);
        $ledger = PointLedger::with(['source' => function (MorphTo $morphTo) {
            $morphTo->morphWith([
                AchievementRecord::class => ['disciplineCase'],
            ]);
        }])
            ->where('student_id', $student->id)
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->latest()
            ->get();

        // View point-ledgers/show tidak merujuk $pointLedger; cukup $student + $ledger.
        return view('point-ledgers.show', ['pointLedger' => null, 'student' => $student, 'ledger' => $ledger]);
    }

    /**
     * Display the specified resource.
     */
    public function show(PointLedger $pointLedger)
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasAnyRole(array_merge(self::STAFF_ROLES, ['siswa'])),
            403,
            'Akses ditolak. Fitur ini hanya untuk pengguna terdaftar.'
        );

        if ($user && $user->hasRole('siswa') && ! $user->hasAnyRole(self::STAFF_ROLES)) {
            $myStudentId = $user->student?->id;
            abort_if(! $myStudentId || $pointLedger->student_id !== $myStudentId, 403, 'Akses ditolak. Anda hanya boleh melihat buku saku poin Anda sendiri.');
        }

        // Scope wali kelas: hanya boleh melihat riwayat poin siswa di kelas binaan
        $waliScope = $this->waliKelasScope();
        if ($waliScope !== null) {
            $studentClassId = $pointLedger->student?->currentEnrollment?->class_id;
            abort_if(
                ! $studentClassId || ! in_array($studentClassId, $waliScope, true),
                403,
                'Akses ditolak. Siswa tersebut bukan anggota kelas yang Anda bimbing.'
            );
        }

        $pointLedger->load(['student.user', 'student.currentEnrollment.schoolClass', 'academicYear', 'creator', 'verifier']);

        $student = $pointLedger->student;

        // Ambil semua transaksi siswa di tahun ajaran yang sama
        $ledger = PointLedger::with(['source' => function (MorphTo $morphTo) {
            $morphTo->morphWith([
                AchievementRecord::class => ['disciplineCase'],
            ]);
        }])
            ->where('student_id', $student->id)
            ->where('academic_year_id', $pointLedger->academic_year_id)
            ->latest()
            ->get();

        return view('point-ledgers.show', compact('pointLedger', 'student', 'ledger'));
    }
}
