<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class NormalizeAccountEmails extends Command
{
    protected $signature = 'accounts:normalize-emails
                            {--apply : Simpan perubahan (tanpa opsi ini hanya simulasi)}';

    protected $description = 'Ubah email akun otomatis lama (siswa.NIS@sekolah.sch.id) ke format NIS@smpbn666.sch.id';

    /** Domain baru untuk akun otomatis. */
    private const DOMAIN = 'smpbn666.sch.id';

    public function handle(): int
    {
        $users = User::withTrashed()
            ->where(function ($query) {
                $query->where('email', 'like', 'siswa.%@sekolah.sch.id')
                    ->orWhere('email', 'like', 'guru.%@sekolah.sch.id');
            })
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            $this->info('Tidak ada email akun otomatis format lama.');

            return self::SUCCESS;
        }

        $apply = (bool) $this->option('apply');
        $rows = [];
        $changed = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $local = Str::before($user->email, '@');
            $identifier = Str::after($local, '.');
            $newLocal = Str::startsWith($local, 'guru.') ? 'guru.'.$identifier : $identifier;
            $newEmail = $newLocal.'@'.self::DOMAIN;

            if (User::withTrashed()->where('email', $newEmail)->whereKeyNot($user->id)->exists()) {
                $rows[] = [$user->id, $user->email, $newEmail, 'dilewati (email sudah dipakai)'];
                $skipped++;

                continue;
            }

            if ($apply) {
                $user->forceFill(['email' => $newEmail])->save();
            }

            $rows[] = [$user->id, $user->email, $newEmail, $apply ? 'diubah' : 'siap diubah'];
            $changed++;
        }

        $this->table(['ID', 'Email lama', 'Email baru', 'Status'], $rows);
        $this->info(($apply ? 'Diubah: ' : 'Siap diubah: ').$changed.' · dilewati: '.$skipped);

        if (! $apply) {
            $this->warn('Simulasi saja. Jalankan dengan --apply untuk menyimpan perubahan.');
        }

        return self::SUCCESS;
    }
}
