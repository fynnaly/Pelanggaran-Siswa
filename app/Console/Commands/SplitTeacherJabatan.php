<?php

namespace App\Console\Commands;

use App\Models\Teacher;
use Illuminate\Console\Command;

class SplitTeacherJabatan extends Command
{
    protected $signature = 'teachers:split-jabatan
                            {--apply : Simpan perubahan (tanpa opsi ini hanya simulasi)}';

    protected $description = 'Pindahkan nilai kolom mata pelajaran hasil import spreadsheet sekolah (JABATAN SEKARANG) ke kolom jabatan';

    public function handle(): int
    {
        $teachers = Teacher::whereNull('jabatan')
            ->whereNotNull('mata_pelajaran')
            ->where('mata_pelajaran', '!=', '')
            ->orderBy('id')
            ->get();

        if ($teachers->isEmpty()) {
            $this->info('Tidak ada data yang perlu dipindahkan.');

            return self::SUCCESS;
        }

        $apply = (bool) $this->option('apply');
        $rows = [];

        foreach ($teachers as $teacher) {
            if ($apply) {
                $teacher->forceFill([
                    'jabatan' => $teacher->mata_pelajaran,
                    'mata_pelajaran' => null,
                ])->save();
            }

            $rows[] = [$teacher->id, $teacher->full_name, $teacher->mata_pelajaran, $apply ? 'dipindah' : 'siap dipindah'];
        }

        $this->table(['ID', 'Nama', 'Nilai sekarang (mata pelajaran)', 'Status'], $rows);
        $this->info(($apply ? 'Dipindah: ' : 'Siap dipindah: ').count($rows));

        if (! $apply) {
            $this->warn('Simulasi saja. Jalankan dengan --apply untuk menyimpan perubahan.');
        }

        return self::SUCCESS;
    }
}
