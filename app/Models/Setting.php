<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** Nilai default bila belum pernah disimpan. Alamat/nama pemilik TTD
     *  sengaja default kosong — informasi pribadi sekolah diisi manual. */
    public const DEFAULTS = [
        'app_name' => 'Pelanggaran Kuy',
        'school_name' => 'SMK Baktinusantara 666',
        'school_address' => '',
        'school_phone' => '(0274) 512345',
        'kop_pemerintah' => 'PEMERINTAH KABUPATEN BANTUL',
        'kop_dinas' => 'DINAS PENDIDIKAN DAN KEBUDAYAAN',
        'signer_kepala_label' => 'Kepala Sekolah',
        'signer_kepala_nama' => '',
        'signer_kepala_nip' => '',
        'signer_kesiswaan_label' => 'Kesiswaan',
        'signer_kesiswaan_nama' => '',
        'signer_kesiswaan_nip' => '',
        'certificate_footer' => 'Sertifikat ini diterbitkan secara elektronik oleh Sistem Informasi Pelanggaran & Prestasi Siswa.',
        'ttd_kepala_path' => null,
        'ttd_kesiswaan_path' => null,
        'logo_path' => null,
    ];

    /**
     * Ambil nilai setting (cache 1 jam). Belum tersimpan → default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, self::DEFAULTS) && $default === null) {
            return null;
        }

        $fallback = $default ?? self::DEFAULTS[$key] ?? null;

        return Cache::remember("settings.{$key}", 3600, function () use ($key, $fallback) {
            return static::where('key', $key)->value('value') ?? $fallback;
        });
    }

    /**
     * Simpan banyak nilai sekaligus + kosongkan cache-nya.
     */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
            Cache::forget("settings.{$key}");
        }
    }
}
