<?php

namespace App\Models;

use App\Enums\CompetitionMatchStatus;
use Database\Factories\CompetitionMatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $competition_id
 * @property int $round
 * @property int $leg
 * @property int $sequence
 * @property int|null $participant_id_home
 * @property int|null $participant_id_away
 * @property int|null $score_home
 * @property int|null $score_away
 * @property int|null $winner_id
 * @property string|null $win_method
 * @property CompetitionMatchStatus $status
 * @property int|null $next_match_id
 * @property int|null $next_slot
 * @property int $result_version
 * @property int|null $result_updated_by
 * @property Carbon|null $result_updated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CompetitionMatch extends Model
{
    /** @use HasFactory<CompetitionMatchFactory> */
    use HasFactory;

    protected $table = 'competition_matches';

    protected $fillable = [
        'competition_id',
        'round',
        'leg',
        'sequence',
        'participant_id_home',
        'participant_id_away',
        'score_home',
        'score_away',
        'winner_id',
        'win_method',
        'status',
        'next_match_id',
        'next_slot',
        'result_version',
        'result_updated_by',
        'result_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'leg' => 'integer',
            'sequence' => 'integer',
            'score_home' => 'integer',
            'score_away' => 'integer',
            'result_version' => 'integer',
            'status' => CompetitionMatchStatus::class,
            'result_updated_at' => 'datetime',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function homeParticipant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'participant_id_home');
    }

    public function awayParticipant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'participant_id_away');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'winner_id');
    }

    public function nextMatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'next_match_id');
    }

    public function resultUpdater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'result_updated_by');
    }

    public function isPending(): bool
    {
        return $this->status === CompetitionMatchStatus::Pending;
    }

    public function isReady(): bool
    {
        return $this->status === CompetitionMatchStatus::Ready;
    }

    public function isCompleted(): bool
    {
        return $this->status === CompetitionMatchStatus::Completed;
    }

    public function isBye(): bool
    {
        return $this->status === CompetitionMatchStatus::Bye;
    }

    public function hasBothParticipants(): bool
    {
        return $this->participant_id_home !== null && $this->participant_id_away !== null;
    }

    public function hasScore(): bool
    {
        return $this->score_home !== null && $this->score_away !== null;
    }
}
