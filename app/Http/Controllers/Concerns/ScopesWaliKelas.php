<?php

namespace App\Http\Controllers\Concerns;

use App\Models\SchoolClass;

/**
 * Scoping RBAC wali kelas — sumber tunggal untuk web dan API.
 *
 * Keputusan: scope HANYA aktif untuk pemilik role wali_kelas yang bukan
 * admin/kesiswaan/kepala_sekolah. Guru tanpa role wali_kelas tetap tanpa
 * scope (perilaku existing tidak berubah), role pengawas tetap penuh.
 *
 * Return: null = tanpa scope, array id kelas = wajib menyaring.
 * Array kosong (wali kelas tanpa kelas aktif) harus menghasilkan data
 * KOSONG, bukan tanpa filter — pemanggil wajib membedakan null vs [].
 */
trait ScopesWaliKelas
{
    private function waliKelasScope(): ?array
    {
        $user = request()->user();

        if (! $user
            || ! $user->hasAnyRole(['wali_kelas', 'guru'])
            || $user->hasAnyRole(['admin', 'kesiswaan', 'kepala_sekolah', 'bk'])) {
            return null;
        }

        // Cari kelas di mana user menjadi wali kelas (homeroom_teacher_id) atau guru pendamping (assistant_teacher_id)
        return SchoolClass::where('homeroom_teacher_id', $user->id)
            ->orWhere('assistant_teacher_id', $user->id)
            ->pluck('id')
            ->all();
    }
}
