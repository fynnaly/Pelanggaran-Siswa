<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    private const MANAGER_ROLES = ['admin', 'kesiswaan', 'kepala_sekolah'];

    /**
     * Halaman pengaturan global. Hanya admin/kesiswaan/kepala_sekolah.
     */
    public function index()
    {
        $this->authorizeAccess();

        $values = [];
        foreach (array_keys(Setting::DEFAULTS) as $key) {
            $values[$key] = Setting::get($key);
        }

        return view('settings.index', compact('values'));
    }

    public function update(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'app_name' => 'required|string|max:80',
            'school_name' => 'required|string|max:120',
            // Alamat bersifat pribadi sekolah — boleh kosong, diatur manual.
            'school_address' => 'nullable|string|max:255',
            'school_phone' => 'nullable|string|max:40',
            'kop_pemerintah' => 'required|string|max:120',
            'kop_dinas' => 'required|string|max:120',
            // Label jabatan (Kepala Sekolah/Kesiswaan) bukan input — statis di
            // Setting::DEFAULTS. Nama & NIP pemilik TTD: informasi pribadi,
            // nullable, boleh dikosongkan.
            'signer_kepala_nama' => 'nullable|string|max:120',
            'signer_kepala_nip' => 'nullable|string|max:40',
            'signer_kesiswaan_nama' => 'nullable|string|max:120',
            'signer_kesiswaan_nip' => 'nullable|string|max:40',
            'certificate_footer' => 'required|string|max:500',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'ttd_kepala' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'ttd_kesiswaan' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
            'remove_ttd_kepala' => 'nullable|boolean',
            'remove_ttd_kesiswaan' => 'nullable|boolean',
        ]);

        $values = [
            'app_name' => $validated['app_name'],
            'school_name' => $validated['school_name'],
            'school_address' => $validated['school_address'] ?? '',
            'school_phone' => $validated['school_phone'] ?? '',
            'kop_pemerintah' => $validated['kop_pemerintah'],
            'kop_dinas' => $validated['kop_dinas'],
            'signer_kepala_nama' => $validated['signer_kepala_nama'] ?? '',
            'signer_kepala_nip' => $validated['signer_kepala_nip'] ?? '',
            'signer_kesiswaan_nama' => $validated['signer_kesiswaan_nama'] ?? '',
            'signer_kesiswaan_nip' => $validated['signer_kesiswaan_nip'] ?? '',
            'certificate_footer' => $validated['certificate_footer'],
        ];

        // Upload / hapus file terpisah — path disimpan di settings, file di storage/app/public.
        foreach ([
            'logo' => 'logo_path',
            'ttd_kepala' => 'ttd_kepala_path',
            'ttd_kesiswaan' => 'ttd_kesiswaan_path',
        ] as $input => $settingKey) {
            $removeFlag = 'remove_' . $input;
            if ($request->boolean($removeFlag)) {
                $old = Setting::get($settingKey);
                if ($old) {
                    Storage::disk('public')->delete($old);
                }
                $values[$settingKey] = null;
            } elseif ($request->hasFile($input)) {
                $old = Setting::get($settingKey);
                if ($old) {
                    Storage::disk('public')->delete($old);
                }
                $values[$settingKey] = $request->file($input)->store('settings', 'public');
            }
        }

        Setting::setMany($values);

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    private function authorizeAccess(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(self::MANAGER_ROLES),
            403,
            'Hanya admin / kesiswaan / kepala sekolah yang boleh mengatur sistem.',
        );
    }
}
