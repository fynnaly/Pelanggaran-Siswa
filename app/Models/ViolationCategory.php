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
     * Poin per batas toleransi pengulangan: [ke-I, ke-II, ke-III].
     *
     * Ke-I = points, ke-II = points + recurring_increment, ke-III =
     * max_recurring_points (nilai persis, bukan plafon). Kategori non-berulang
     * mengembalikan satu nilai saja; pelanggaran ke-4 dst. tetap memakai batas
     * terakhir dari daftar ini.
     *
     * Contoh P1.1 Terlambat < 10 menit (5/10/15) → [5, 10, 15].
     */
    public function tierPoints(): array
    {
        $base = (int) $this->points;

        if (! $this->is_recurring || ! $this->recurring_increment) {
            return [$base];
        }

        $tiers = [$base, $base + (int) $this->recurring_increment];

        // Abaikan batas ke-III yang 0 / tidak naik (toleransi berhenti di ke-II).
        if ($this->max_recurring_points !== null && (int) $this->max_recurring_points > end($tiers)) {
            $tiers[] = (int) $this->max_recurring_points;
        }

        return $tiers;
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

    /**
     * Daftar prestasi pemulihan untuk kategori ini.
     * Sumber: redemption_rules (padanan kanonik per sub). Zero tolerance
     * → [] (tidak ada jalur penebusan via prestasi).
     */
    public function recoveryOptions(): array
    {
        if ($this->is_zero_tolerance) {
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

        $max = match ($this->severity) {
            'ringan' => 10,
            'sedang' => 20,
            'berat'  => 1000,
        };

        return AchievementCategory::where('status', 'active')
            ->whereNotNull('parent_id')
            ->where('points', '<=', $max)
            ->orderBy('points', 'desc')
            ->limit(3)
            ->get()
            ->map(fn ($ach) => [
                'code' => $ach->code,
                'name' => $ach->name,
                'poin' => $ach->points,
            ])->all();
    }

    /**
     * Teks sanksi: daftar prestasi wajib untuk memulihkan poin.
     * Bukan label statis — mengikuti redemption rules.
     * Tanpa prestasi divalidasi, poin tidak naik (poin tertahan).
     */
    public function resolveSanksi(): string
    {
        if ($this->is_zero_tolerance) {
            return 'Zero tolerance — Program Pembinaan Intensif. Tidak bisa ditebus poin prestasi.';
        }

        $options = $this->recoveryOptions();

        if ($options === []) {
            return 'Tidak ada jalur pemulihan via prestasi. Poin tetap sampai ada kebijakan manual.';
        }

        $list = collect($options)
            ->map(fn ($row) => "{$row['code']} {$row['name']} (+{$row['poin']})")
            ->implode('; ');

        return "Wajib raih prestasi: {$list}. Tanpa prestasi divalidasi, poin tertahan (tidak naik).";
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
