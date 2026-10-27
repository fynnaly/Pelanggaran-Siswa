<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViolationCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'sanksi',
        'severity',
        'points',
        'group_type',
        'parent_id',
        'is_zero_tolerance',
        'is_recurring',
        'recurring_increment',
        'max_recurring_points',
        'status',
        'effective_date',
        'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'is_zero_tolerance' => 'boolean',
            'is_recurring' => 'boolean',
            'effective_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function disciplineCases(): HasMany
    {
        return $this->hasMany(DisciplineCase::class);
    }

    /** Kategori induk (null = kategori induk). */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Daftar sub-kategori di bawah kategori ini. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** Rekomendasi pemulihan otomatis (sub-prestasi padanan) untuk pelanggaran ini. */
    public function redemptionRules(): HasMany
    {
        return $this->hasMany(RedemptionRule::class);
    }

    /** Jumlah batas toleransi pengulangan: ke-I, ke-II, ke-III. */
    public const MAX_TIER = 3;

    /** Kategori induk = baris tanpa parent_id. */
    public function isParent(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Poin per tingkat toleransi: [ke-I, ke-II, ke-III].
     *
     * Skala sekolah (S2/S3/S4/S5): 8 / 16 / 32 / 64. Pengulangan naik satu
     * anak tangga, maksimal 3 tingkat; ke-4 dst. memakai nilai ke-III.
     * Plafon 32 — nilai 64 hanya untuk zero tolerance (tidak bisa ditebus).
     */
    public function tierPoints(): array
    {
        $base = (int) $this->points;

        if ($this->is_zero_tolerance || $base >= 64) {
            return [$base];
        }

        $plafon = 32;

        return [
            min($base * 1, $plafon),
            min($base * 2, $plafon),
            min($base * 4, $plafon),
        ];
    }

    /**
     * Poin untuk pelanggaran ke-$occurrence.
     * Melewati batas akhir (occurrence > 3) tetap memakai poin batas terakhir.
     */
    public function pointsForOccurrence(int $occurrence): int
    {
        $tiers = $this->tierPoints();
        $index = max($occurrence, 1) - 1;

        return $tiers[min($index, count($tiers) - 1)];
    }

    /** Sanksi dari daftar Bagian 40 dipetakan ke tingkat poin baru 8/16/32/64. */
    public static function sanksiForPoints(int $points): string
    {
        return match (true) {
            $points <= 8 => 'Teguran',
            $points <= 16 => 'Teguran dan penugasan',
            $points <= 32 => 'Pemanggilan orang tua atau skorsing',
            default => 'Dikeluarkan dari sekolah / Intervensi Khusus',
        };
    }

    /**
     * Daftar prestasi pemulihan untuk kategori ini.
     * Sumber: redemption_rules (padanan kanonik per sub). Zero tolerance
     * → [] (tidak ada jalur penebusan via prestasi).
     */
    public function recoveryOptions(): array
    {
        if ($this->is_zero_tolerance || (int) $this->points >= 64) {
            return [];
        }

        $rules = $this->redemptionRules()->with('achievementCategory')->get();

        if ($rules->isNotEmpty()) {
            return $rules->map(fn ($rule) => [
                'code' => $rule->achievementCategory?->code,
                'name' => $rule->achievementCategory?->name,
                'poin' => $rule->poin_penebus,
            ])->filter(fn ($row) => $row['name'] !== null)->values()->all();
        }

        return [];
    }

    /**
     * Teks sanksi: daftar prestasi wajib untuk memulihkan poin.
     * Bukan label statis — mengikuti redemption rules.
     * Tanpa prestasi divalidasi, poin tidak naik (poin tertahan).
     */
    public function resolveSanksi(): string
    {
        if ($this->is_zero_tolerance || (int) $this->points >= 64) {
            return 'Zero tolerance — Program Pembinaan Intensif. Tidak bisa ditebus poin prestasi.';
        }

        $baseSanksi = $this->sanksi ?: self::sanksiForPoints((int) $this->points);

        $options = $this->recoveryOptions();

        if ($options === []) {
            return $baseSanksi;
        }

        $list = collect($options)
            ->map(fn ($row) => "{$row['code']} {$row['name']} (+{$row['poin']})")
            ->implode('; ');

        return "{$baseSanksi}. Wajib raih prestasi: {$list}. Tanpa prestasi divalidasi, poin tertahan.";
    }

    /** Sub-kategori aktif = kandidat pemilihan pada form kasus. */
    public function isLeafActive(): bool
    {
        return $this->parent_id !== null && $this->isActive();
    }

    /**
     * Cek apakah kategori aktif (berdasarkan status + rentang tanggal).
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && (!$this->effective_date || $this->effective_date->isPast())
            && (!$this->expiry_date || $this->expiry_date->isFuture());
    }
}
