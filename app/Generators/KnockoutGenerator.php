<?php

namespace App\Generators;

use App\Enums\CompetitionMatchStatus;
use App\Values\DrawResult;
use App\Values\DrawSlot;

class KnockoutGenerator implements MatchGenerator
{
    public function formatLabel(): string
    {
        return 'knockout';
    }

    /**
     * Generate a complete knockout bracket.
     *
     * Total matches = bracketSize - 1
     * Bye matches   = bracketSize - n (a match is a "bye" when at most one
     *                 parent side has an advancing participant).
     * Scored        = n - 1 (matches requiring an actual result).
     */
    public function generate(array $participantIds): DrawResult
    {
        $n = count($participantIds);
        if ($n < 2) {
            return new DrawResult([]);
        }

        $bracketSize = $this->nextPowerOfTwo($n);
        $totalRounds = (int) round(log($bracketSize, 2));

        [$rounds, $hasAdvancer] = $this->buildBracketTree($participantIds, $bracketSize);

        return $this->toDrawSlots($rounds, $hasAdvancer, $totalRounds);
    }

    /**
     * @param  list<int>  $participantIds
     * @return array{0: list<list<array{home: int|null, away: int|null, status: CompetitionMatchStatus}>>, 1: list<list<bool>>}
     */
    private function buildBracketTree(array $participantIds, int $bracketSize): array
    {
        $n = count($participantIds);
        $totalRounds = (int) round(log($bracketSize, 2));

        $rounds = [];
        $hasAdvancer = [];
        $current = [];
        $currentAdv = [];

        for ($i = 0; $i < $bracketSize / 2; $i++) {
            $home = ($i * 2 < $n) ? $participantIds[$i * 2] : null;
            $away = ($i * 2 + 1 < $n) ? $participantIds[$i * 2 + 1] : null;

            if ($home !== null && $away !== null) {
                $current[] = ['home' => $home, 'away' => $away, 'status' => CompetitionMatchStatus::Ready];
                $currentAdv[] = true;
            } elseif ($home !== null) {
                $current[] = ['home' => $home, 'away' => null, 'status' => CompetitionMatchStatus::Bye];
                $currentAdv[] = true;
            } elseif ($away !== null) {
                $current[] = ['home' => $away, 'away' => null, 'status' => CompetitionMatchStatus::Bye];
                $currentAdv[] = true;
            } else {
                $current[] = ['home' => null, 'away' => null, 'status' => CompetitionMatchStatus::Bye];
                $currentAdv[] = false;
            }
        }

        $rounds[] = $current;
        $hasAdvancer[] = $currentAdv;

        for ($round = 1; $round < $totalRounds; $round++) {
            $previous = $current;
            $previousAdv = $currentAdv;
            $current = [];
            $currentAdv = [];

            for ($i = 0; $i < count($previous) / 2; $i++) {
                $aHasAdv = $previousAdv[$i * 2];
                $bHasAdv = $previousAdv[$i * 2 + 1];

                if ($aHasAdv && $bHasAdv) {
                    $current[] = ['home' => null, 'away' => null, 'status' => CompetitionMatchStatus::Pending];
                    $currentAdv[] = true;
                } elseif ($aHasAdv || $bHasAdv) {
                    $current[] = ['home' => null, 'away' => null, 'status' => CompetitionMatchStatus::Bye];
                    $currentAdv[] = true;
                } else {
                    $current[] = ['home' => null, 'away' => null, 'status' => CompetitionMatchStatus::Bye];
                    $currentAdv[] = false;
                }
            }

            $rounds[] = $current;
            $hasAdvancer[] = $currentAdv;
        }

        return [$rounds, $hasAdvancer];
    }

    /**
     * @param  list<list<array{home: int|null, away: int|null, status: CompetitionMatchStatus}>>  $rounds
     * @param  list<list<bool>>  $hasAdvancer
     */
    private function toDrawSlots(array $rounds, array $hasAdvancer, int $totalRounds): DrawResult
    {
        $slots = [];
        $idMap = [];
        $seq = 0;

        foreach ($rounds as $round => $matches) {
            $idMap[$round] = [];
            foreach ($matches as $pos => $match) {
                $seq++;
                $idMap[$round][$pos] = $seq;
            }
        }

        foreach ($rounds as $round => $matches) {
            $roundNumber = $round + 1;

            foreach ($matches as $pos => $match) {
                $sequence = $idMap[$round][$pos];

                $nextMatchId = null;
                $nextSlot = null;

                if ($roundNumber < $totalRounds) {
                    $nextPos = (int) floor($pos / 2);
                    $nextMatchId = $idMap[$round + 1][$nextPos] ?? null;
                    $nextSlot = ($pos % 2 === 0) ? 1 : 2;
                }

                $slots[] = new DrawSlot(
                    round: $roundNumber,
                    leg: 1,
                    sequence: $sequence,
                    homeId: $match['home'],
                    awayId: $match['away'],
                    status: $match['status'],
                    nextMatchId: $nextMatchId,
                    nextSlot: $nextSlot,
                );
            }
        }

        return new DrawResult($slots);
    }

    private function nextPowerOfTwo(int $n): int
    {
        if ($n < 2) {
            return 2;
        }

        $power = 1;
        while ($power < $n) {
            $power <<= 1;
        }

        return $power;
    }
}
