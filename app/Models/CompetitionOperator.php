<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $competition_id
 * @property int $user_id
 * @property int|null $assigned_by
 * @property Carbon $assigned_at
 */
class CompetitionOperator extends Pivot
{
    protected $table = 'competition_operator';

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
