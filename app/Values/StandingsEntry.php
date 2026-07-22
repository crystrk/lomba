<?php

namespace App\Values;

use JsonSerializable;

final readonly class StandingsEntry implements JsonSerializable
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

    public function jsonSerialize(): array
    {
        return [
            'rank' => $this->rank,
            'participant_id' => $this->participantId,
            'participantId' => $this->participantId,
            'participant_name' => $this->participantName,
            'participantName' => $this->participantName,
            'played' => $this->played,
            'won' => $this->won,
            'drawn' => $this->drawn,
            'lost' => $this->lost,
            'score_for' => $this->scoreFor,
            'scoreFor' => $this->scoreFor,
            'score_against' => $this->scoreAgainst,
            'scoreAgainst' => $this->scoreAgainst,
            'difference' => $this->difference,
            'points' => $this->points,
        ];
    }
}
