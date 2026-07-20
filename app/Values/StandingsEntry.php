<?php

namespace App\Values;

final readonly class StandingsEntry
{
    public function __construct(
        public int $rank,
        public int $participantId,
        public string $participantName,
        public int $played,
        public int $won,
        public int $drawn,
        public int $lost,
        public int $scoreFor,
        public int $scoreAgainst,
        public int $difference,
        public int $points,
    ) {}
}
