<?php

namespace App\Generators;

use App\Enums\CompetitionMatchStatus;
use App\Values\DrawResult;
use App\Values\DrawSlot;

class HalfCompetitionGenerator implements MatchGenerator
{
    public function formatLabel(): string
    {
        return 'half_competition';
    }

    /**
     * Single round-robin using the circle method.
     *
     * Even n: n-1 rounds, each participant plays every round.
     * Odd n:  n rounds, each round one bye.
     *
     * The participant order array drives the fixture order deterministically.
     */
    public function generate(array $participantIds): DrawResult
    {
        $n = count($participantIds);
        if ($n < 2) {
            return new DrawResult([]);
        }

        $wheel = $participantIds;
        $isOdd = $n % 2 !== 0;

        if ($isOdd) {
            $wheel[] = null;
        }

        $rounds = count($wheel) - 1;
        $slots = [];
        $sequence = 0;

        for ($round = 1; $round <= $rounds; $round++) {
            $pairsInRound = intdiv(count($wheel), 2);

            for ($p = 0; $p < $pairsInRound; $p++) {
                $home = $wheel[$p];
                $away = $wheel[count($wheel) - 1 - $p];

                if ($home === null || $away === null) {
                    continue;
                }

                $sequence++;
                $slots[] = new DrawSlot(
                    round: $round,
                    leg: 1,
                    sequence: $sequence,
                    homeId: $home,
                    awayId: $away,
                    status: CompetitionMatchStatus::Ready,
                );
            }

            $last = array_pop($wheel);
            array_splice($wheel, 1, 0, [$last]);
        }

        return new DrawResult($slots);
    }
}
