<?php

namespace App\Values;

use App\Enums\CompetitionMatchStatus;

final readonly class DrawSlot
{
    public function __construct(
        public int $round,
        public int $leg,
        public int $sequence,
        public ?int $homeId,
        public ?int $awayId,
        public CompetitionMatchStatus $status,
        public ?int $nextMatchId = null,
        public ?int $nextSlot = null,
    ) {}
}
