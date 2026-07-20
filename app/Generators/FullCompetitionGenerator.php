<?php

namespace App\Generators;

use App\Values\DrawResult;
use App\Values\DrawSlot;

class FullCompetitionGenerator implements MatchGenerator
{
    private HalfCompetitionGenerator $halfGenerator;

    public function __construct()
    {
        $this->halfGenerator = new HalfCompetitionGenerator;
    }

    public function formatLabel(): string
    {
        return 'full_competition';
    }

    /**
     * Double round-robin: leg 1 is a half-competition, leg 2 repeats with
     * reversed home/away. Round numbers of leg 2 are offset by the number
     * of rounds in leg 1.
     */
    public function generate(array $participantIds): DrawResult
    {
        $half = $this->halfGenerator->generate($participantIds);
        $maxRound = 0;
        $slots = [];

        foreach ($half->slots as $slot) {
            $slots[] = $slot;
            $maxRound = max($maxRound, $slot->round);
        }

        foreach ($half->slots as $slot) {
            $slots[] = new DrawSlot(
                round: $slot->round + $maxRound,
                leg: 2,
                sequence: $slot->sequence + count($half->slots),
                homeId: $slot->awayId,
                awayId: $slot->homeId,
                status: $slot->status,
            );
        }

        return new DrawResult($slots);
    }
}
