<?php

namespace App\Generators;

use App\Enums\CompetitionMatchStatus;
use App\Values\DrawResult;
use App\Values\DrawSlot;

class GroupFinalFourGenerator implements MatchGenerator
{
    public function formatLabel(): string
    {
        return 'group_final_four';
    }

    public function generate(array $participantIds): DrawResult
    {
        $n = count($participantIds);
        if ($n <= 4 || $n % 2 !== 0) {
            return new DrawResult([]);
        }

        $halfCount = intdiv($n, 2);
        $groupAParticipants = array_slice($participantIds, 0, $halfCount);
        $groupBParticipants = array_slice($participantIds, $halfCount, $halfCount);

        $halfGen = new HalfCompetitionGenerator;
        $resultA = $halfGen->generate($groupAParticipants);
        $resultB = $halfGen->generate($groupBParticipants);

        $slots = [];
        $sequence = 0;
        $maxRound = 0;

        foreach ($resultA->slots as $slot) {
            $sequence++;
            if ($slot->round > $maxRound) {
                $maxRound = $slot->round;
            }

            $slots[] = new DrawSlot(
                round: $slot->round,
                leg: $slot->leg,
                sequence: $sequence,
                homeId: $slot->homeId,
                awayId: $slot->awayId,
                status: $slot->status,
                matchType: 'group_a',
            );
        }

        foreach ($resultB->slots as $slot) {
            $sequence++;
            if ($slot->round > $maxRound) {
                $maxRound = $slot->round;
            }

            $slots[] = new DrawSlot(
                round: $slot->round,
                leg: $slot->leg,
                sequence: $sequence,
                homeId: $slot->homeId,
                awayId: $slot->awayId,
                status: $slot->status,
                matchType: 'group_b',
            );
        }

        $finalRound = $maxRound + 1;

        // Final 3–4 (Perebutan Tempat Ke-3)
        $sequence++;
        $slots[] = new DrawSlot(
            round: $finalRound,
            leg: 1,
            sequence: $sequence,
            homeId: null,
            awayId: null,
            status: CompetitionMatchStatus::Pending,
            matchType: 'third_place',
        );

        // Final 1–2 (Grand Final / Perebutan Juara 1)
        $sequence++;
        $slots[] = new DrawSlot(
            round: $finalRound,
            leg: 1,
            sequence: $sequence,
            homeId: null,
            awayId: null,
            status: CompetitionMatchStatus::Pending,
            matchType: 'final',
        );

        return new DrawResult($slots);
    }
}
