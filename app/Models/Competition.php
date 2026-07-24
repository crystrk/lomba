<?php

namespace App\Models;

use App\Enums\CompetitionFormat;
use App\Enums\CompetitionSport;
use App\Enums\CompetitionStatus;
use Database\Factories\CompetitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property CompetitionSport|null $sport
 * @property CompetitionFormat $format
 * @property CompetitionStatus $status
 * @property int|null $win_points
 * @property int|null $draw_points
 * @property int|null $loss_points
 * @property int $draw_version
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int|null $locked_by
 * @property Carbon|null $locked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'slug', 'description', 'sport', 'format', 'status',
    'win_points', 'draw_points', 'loss_points',
    'draw_version', 'starts_at', 'ends_at',
    'locked_by', 'locked_at',
    'is_results_locked', 'results_locked_by', 'results_locked_at',
])]
class Competition extends Model
{
    /** @use HasFactory<CompetitionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'format' => CompetitionFormat::class,
            'sport' => CompetitionSport::class,
            'status' => CompetitionStatus::class,
            'win_points' => 'integer',
            'draw_points' => 'integer',
            'loss_points' => 'integer',
            'draw_version' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'locked_at' => 'datetime',
            'is_results_locked' => 'boolean',
            'results_locked_at' => 'datetime',
        ];
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function resultsLocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'results_locked_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(CompetitionMatch::class);
    }

    public function operators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'competition_operator')
            ->using(CompetitionOperator::class)
            ->withPivot('assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    public function isDraft(): bool
    {
        return $this->status === CompetitionStatus::Draft;
    }

    public function isDrawn(): bool
    {
        return $this->status === CompetitionStatus::Drawn;
    }

    public function isLocked(): bool
    {
        return $this->status === CompetitionStatus::Locked;
    }

    public function isInProgress(): bool
    {
        return $this->status === CompetitionStatus::InProgress;
    }

    public function isCompleted(): bool
    {
        return $this->status === CompetitionStatus::Completed;
    }

    public function isActive(): bool
    {
        return $this->isLocked() || $this->isInProgress() || $this->isCompleted();
    }

    public function isResultsLocked(): bool
    {
        return (bool) $this->is_results_locked;
    }

    public function isEditable(): bool
    {
        return $this->isDraft() || $this->isDrawn();
    }

    public function isKnockout(): bool
    {
        return $this->format === CompetitionFormat::Knockout;
    }

    public function usesPoints(): bool
    {
        return ! $this->isKnockout();
    }
}
