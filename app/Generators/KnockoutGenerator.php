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

        $byes = $bracketSize - $n;
        $m1 = (2 * $n) - $bracketSize; // Number of participants playing in Round 1 2-player matches
        $readyMatchesR1 = intdiv($m1, 2);

        $rounds = [];
        $hasAdvancer = [];
        $current = [];
        $currentAdv = [];
        $advancerValues = [];

        // Round 1 (r = 0)
        for ($k = 0; $k < $bracketSize / 2; $k++) {
            if ($k < $readyMatchesR1) {
                $home = $participantIds[$k * 2];
                $away = $participantIds[$k * 2 + 1];
                $current[] = ['home' => $home, 'away' => $away, 'status' => CompetitionMatchStatus::Ready];
                $currentAdv[] = false;
                $advancerValues[] = null;
            } else {
                $pIndex = $m1 + ($k - $readyMatchesR1);
                $home = ($pIndex < $n) ? $participantIds[$pIndex] : null;
                $status = $home !== null ? CompetitionMatchStatus::Bye : CompetitionMatchStatus::Bye;
                $current[] = ['home' => $home, 'away' => null, 'status' => $status];
                $currentAdv[] = $home !== null;
                $advancerValues[] = $home;
            }
        }

        $rounds[] = $current;
        $hasAdvancer[] = $currentAdv;

        // Subsequent rounds
        for ($round = 1; $round < $totalRounds; $round++) {
            $prevMatches = $current;
            $prevAdv = $currentAdv;
            $prevAdvValues = $advancerValues;

            $current = [];
            $currentAdv = [];
            $advancerValues = [];

            for ($k = 0; $k < count($prevMatches) / 2; $k++) {
                $parentA = $prevMatches[$k * 2];
                $parentB = $prevMatches[$k * 2 + 1];

                $parentAAdv = $prevAdvValues[$k * 2];
                $parentBAdv = $prevAdvValues[$k * 2 + 1];

                $home = $parentA['status'] === CompetitionMatchStatus::Bye ? $parentAAdv : null;
                $away = $parentB['status'] === CompetitionMatchStatus::Bye ? $parentBAdv : null;

                $bothParentsBye = ($parentA['status'] === CompetitionMatchStatus::Bye)
                    && ($parentB['status'] === CompetitionMatchStatus::Bye);

                if ($bothParentsBye) {
                    if ($home !== null && $away !== null) {
                        $current[] = ['home' => $home, 'away' => $away, 'status' => CompetitionMatchStatus::Ready];
                        $currentAdv[] = false;
                        $advancerValues[] = null;
                    } elseif ($home !== null) {
                        $current[] = ['home' => $home, 'away' => null, 'status' => CompetitionMatchStatus::Bye];
                        $currentAdv[] = true;
                        $advancerValues[] = $home;
                    } elseif ($away !== null) {
                        $current[] = ['home' => $away, 'away' => null, 'status' => CompetitionMatchStatus::Bye];
                        $currentAdv[] = true;
                        $advancerValues[] = $away;
                    } else {
                        $current[] = ['home' => null, 'away' => null, 'status' => CompetitionMatchStatus::Bye];
                        $currentAdv[] = false;
                        $advancerValues[] = null;
                    }
                } else {
                    $status = ($home !== null && $away !== null)
                        ? CompetitionMatchStatus::Ready
                        : CompetitionMatchStatus::Pending;

                    $current[] = ['home' => $home, 'away' => $away, 'status' => $status];
                    $currentAdv[] = false;
                    $advancerValues[] = null;
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
