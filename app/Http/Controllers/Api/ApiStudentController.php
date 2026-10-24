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

    /**
     * Ledger poin satu siswa + saldo/zone — untuk layar detail di Android.
     * Siswa di luar kelas yang diampu wali kelas ditolak 403 di sini,
     * bukan hanya lewat query list, supaya ID yang ditebak tidak bocor.
     */
    public function points(Request $request, Student $student): JsonResponse
    {
        $scopeClassIds = $this->waliKelasScope();

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
}
