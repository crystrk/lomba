<?php

namespace App\Generators;

use App\Enums\CompetitionMatchStatus;
use App\Values\DrawResult;
use App\Values\DrawSlot;

class FinalFourGenerator implements MatchGenerator
{
    public function formatLabel(): string
    {
        return 'final_four';
    }

    public function generate(array $participantIds): DrawResult
    {
        $n = count($participantIds);
        if ($n < 2) {
            return new DrawResult([]);
        }

        $knockoutGen = new KnockoutGenerator;
        $baseDrawResult = $knockoutGen->generate($participantIds);
        $slots = $baseDrawResult->slots;

        if (empty($slots)) {
            return new DrawResult([]);
        }

        // Determine total rounds
        $totalRounds = 0;
        foreach ($slots as $slot) {
            if ($slot->round > $totalRounds) {
                $totalRounds = $slot->round;
            }
        }

        // If we don't have at least 2 rounds (which requires 4+ participants),
        // fallback to standard slots.
        if ($totalRounds < 2) {
            return $baseDrawResult;
        }

        // Identify final match (round == totalRounds, sequence is max in that round)
        $finalSequence = null;
        foreach ($slots as $slot) {
            if ($slot->round === $totalRounds) {
                $finalSequence = $slot->sequence;
                break;
            }
        }

        // Sequence number for 3rd place play-off match (added right after final match)
        $thirdPlaceSequence = $finalSequence + 1;

        $newSlots = [];
        $semifinalSlots = [];

        foreach ($slots as $slot) {
            $round = $slot->round;
            $seq = $slot->sequence;
            $matchType = 'standard';

            if ($round === $totalRounds) {
                $matchType = 'final';
            } elseif ($round === $totalRounds - 1) {
                $matchType = 'semifinal';
            }

            $nextMatchId = $slot->nextMatchId;
            $nextSlot = $slot->nextSlot;
            $loserNextMatchId = null;
            $loserNextSlot = null;

            if ($matchType === 'semifinal') {
                $semifinalSlots[] = $seq;
                // Position in semifinal (first SF -> slot 1, second SF -> slot 2)
                $sfIndex = count($semifinalSlots);
                $nextMatchId = $finalSequence;
                $nextSlot = $sfIndex;
                $loserNextMatchId = $thirdPlaceSequence;
                $loserNextSlot = $sfIndex;
            }

            $newSlots[] = new DrawSlot(
                round: $slot->round,
                leg: $slot->leg,
                sequence: $slot->sequence,
                homeId: $slot->homeId,
                awayId: $slot->awayId,
                status: $slot->status,
                nextMatchId: $nextMatchId,
                nextSlot: $nextSlot,
                loserNextMatchId: $loserNextMatchId,
                loserNextSlot: $loserNextSlot,
                matchType: $matchType,
            );
        }

        // Add 3rd-4th Place Play-off match (Perebutan Juara Ke-3) in final round
        $newSlots[] = new DrawSlot(
            round: $totalRounds,
            leg: 1,
            sequence: $thirdPlaceSequence,
            homeId: null,
            awayId: null,
            status: CompetitionMatchStatus::Pending,
            nextMatchId: null,
            nextSlot: null,
            loserNextMatchId: null,
            loserNextSlot: null,
            matchType: 'third_place',
        );

        return new DrawResult($newSlots);
    }
}
