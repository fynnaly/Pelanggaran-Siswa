<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedemptionRule extends Model
{
    protected $fillable = [
        'violation_category_id',
        'achievement_category_id',
        'poin_penebus',
    ];

    public function violationCategory(): BelongsTo
    {
        return $this->belongsTo(ViolationCategory::class);
    }

    public function achievementCategory(): BelongsTo
    {
        return $this->belongsTo(AchievementCategory::class);
    }
}
