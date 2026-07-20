<?php

namespace App\Models;

use App\Enums\CompetitionStatus;
use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $competition_id
 * @property string $name
 * @property string $normalized_name
 * @property string|null $short_name
 * @property string|null $logo_path
 * @property int|null $draw_position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory;

    protected $fillable = [
        'competition_id',
        'name',
        'normalized_name',
        'short_name',
        'logo_path',
        'draw_position',
    ];

    protected static function booted(): void
    {
        static::saving(function (Participant $participant): void {
            if ($participant->isDirty('name')) {
                $participant->normalized_name = Str::lower($participant->name);
            }
        });

        static::saved(function (Participant $participant): void {
            $competition = $participant->competition;
            if ($competition !== null && $competition->isDrawn()) {
                $competition->matches()->delete();
                $competition->update(['status' => CompetitionStatus::Draft]);
            }
        });

        static::deleted(function (Participant $participant): void {
            $competition = $participant->competition()->first();
            if ($competition !== null && $competition->isDrawn()) {
                $competition->matches()->delete();
                $competition->update(['status' => CompetitionStatus::Draft]);
            }
        });
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }
}
