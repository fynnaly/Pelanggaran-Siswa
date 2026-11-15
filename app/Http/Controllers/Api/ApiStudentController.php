<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ScopesWaliKelas;
use App\Http\Controllers\Controller;
use App\Models\PointLedger;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiStudentController extends Controller
{
    use ScopesWaliKelas;

    private const STAFF_ROLES = ['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'];

    /**
     * Pencarian siswa instan (autocompletion) nama / NIS / NISN.
     */
    public function search(Request $request): JsonResponse
    {
        if ($response = $this->authorizeStaff($request)) {
            return $response;
        }

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $limit = min(max($request->integer('limit', 10), 1), 50);

        $scopeClassIds = $this->waliKelasScope();
        $students = Student::query()
            ->where(function ($query) use ($q) {
                $query->where('full_name', 'like', "%{$q}%")
                    ->orWhere('nis', 'like', "%{$q}%")
                    ->orWhere('nisn', 'like', "%{$q}%");
            })
            ->when($scopeClassIds !== null, fn ($query) => $query->whereHas(
                'currentEnrollment',
                fn ($enrollmentQuery) => $enrollmentQuery->whereIn('class_id', $scopeClassIds)
            ))
            ->with(['enrollments.schoolClass'])
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $students->map(fn (Student $s) => [
                'id' => $s->id,
                'nis' => $s->nis,
                'nisn' => $s->nisn,
                'nama' => $s->full_name,
                'kelas' => $s->class_name,
                'saldo_poin' => $s->current_balance,
                'zona' => $s->zone['name'] ?? 'Normal',
            ]),
        ]);
    }

    /**
     * Scan nametag NFC untuk lookup profil & saldo cepat.
     */
    public function byNfc(Request $request, string $uid): JsonResponse
    {
        if ($response = $this->authorizeStaff($request)) {
            return $response;
        }

        $cleanUid = trim($uid);

        $student = Student::query()
            ->whereRaw('LOWER(nfc_uid) = ?', [strtolower($cleanUid)])
            ->with(['enrollments.schoolClass'])
            ->first();

        if (! $student) {
            return response()->json([
                'message' => 'Nametag belum terdaftar pada siswa mana pun.',
            ], 404);
        }

        $scopeClassIds = $this->waliKelasScope();
        if ($scopeClassIds !== null && ! $student->currentEnrollment()->whereIn('class_id', $scopeClassIds)->exists()) {
            return response()->json(['message' => 'Siswa di luar kelas yang Anda ampu.'], 403);
        }

        $zone = $student->zone;
        $colorName = match ($zone['color'] ?? 'success') {
            'danger' => 'merah',
            'warning' => 'oranye',
            'info' => 'biru-muda',
            'primary' => 'biru',
            default => 'hijau',
        };

        return response()->json([
            'siswa' => [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'nis' => $student->nis,
                'nama' => $student->full_name,
                'kelas' => $student->class_name,
            ],
            'saldo' => $student->current_balance,
            'zona' => [
                'nama' => $zone['name'] ?? 'Normal',
                'warna' => $colorName,
                'penanggung_jawab' => $zone['responsible'] ?? 'Wali Kelas',
                'tindakan' => $zone['action'] ?? 'Pembinaan rutin',
            ],
        ]);
    }

    /**
     * Ledger poin satu siswa + saldo/zone — untuk layar detail di Android.
     * Siswa di luar kelas yang diampu wali kelas ditolak 403 di sini,
     * bukan hanya lewat query list, supaya ID yang ditebak tidak bocor.
     */
    public function points(Request $request, Student $student): JsonResponse
    {
        $user = $request->user();
        $isStaff = $user?->hasAnyRole(self::STAFF_ROLES) ?? false;
        $isStudent = $user?->hasRole('siswa') && ! $isStaff;

        abort_unless($isStaff || $isStudent, 403, 'Akses ditolak. Fitur ini hanya untuk pengguna terdaftar.');

        if ($isStudent) {
            abort_unless(
                $user->student?->id === $student->id,
                403,
                'Akses ditolak. Anda hanya dapat melihat poin milik sendiri.'
            );
        }

        $scopeClassIds = $isStaff ? $this->waliKelasScope() : null;
        if ($scopeClassIds !== null) {
            $inScope = Student::whereKey($student->id)
                ->whereHas('currentEnrollment', fn ($q) => $q->whereIn('class_id', $scopeClassIds))
                ->exists();

            if (! $inScope) {
                return response()->json(['message' => 'Siswa di luar kelas yang Anda ampu.'], 403);
            }
        }

        $yearId = $request->integer('year_id') ?: null;

        $ledgers = $student->pointLedgers()
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->orderByDesc('id')
            ->paginate(50);

        $zone = $student->zone;

        return response()->json([
            'siswa' => [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'nis' => $student->nis,
                'nama' => $student->full_name,
                'kelas' => $student->class_name,
            ],
            'saldo' => $student->current_balance,
            'zona' => [
                'nama' => $zone['name'],
                'penanggung_jawab' => $zone['responsible'],
                'tindakan' => $zone['action'],
            ],
            'ledger' => [
                'total' => $ledgers->total(),
                'data' => $ledgers->getCollection()->map(fn (PointLedger $l) => [
                    'id' => $l->id,
                    'arah' => $l->direction,
                    'jumlah' => $l->amount,
                    'saldo_setelah' => $l->balance_after,
                    'tipe' => $l->transaction_type,
                    'alasan' => $l->reason,
                    'waktu' => $l->created_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * Restrict student search and NFC lookup to school staff.
     */
    private function authorizeStaff(Request $request): ?JsonResponse
    {
        if (! $request->user()?->hasAnyRole(self::STAFF_ROLES)) {
            return response()->json(['message' => 'Akses ditolak. Fitur ini hanya untuk staf sekolah.'], 403);
        }

        return null;
    }
}
