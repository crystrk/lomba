<?php

namespace App\Calculators;

use App\Models\Competition;
use App\Models\CompetitionMatch;
use App\Models\Participant;
use App\Values\StandingsEntry;
use Illuminate\Support\Collection;

class StandingsCalculator
{
    /**
     * @param  Collection<int, Participant>  $participants
     * @param  Collection<int, CompetitionMatch>  $completedMatches
     * @return list<StandingsEntry>
     */
    public function calculate(Competition $competition, Collection $participants, Collection $completedMatches): array
    {
        $winPoints = $competition->win_points ?? 3;
        $drawPoints = $competition->draw_points ?? 1;
        $lossPoints = $competition->loss_points ?? 0;

        $stats = [];

        foreach ($participants as $participant) {
            $stats[$participant->id] = [
                'participant_id' => $participant->id,
                'participant_name' => $participant->name,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'score_for' => 0,
                'score_against' => 0,
            ];
        }

        foreach ($completedMatches as $match) {
            if ($match->isBye() || $match->score_home === null || $match->score_away === null) {
                continue;
            }

            $homeId = $match->participant_id_home;
            $awayId = $match->participant_id_away;

            if (! isset($stats[$homeId]) || ! isset($stats[$awayId])) {
                continue;
            }

            $stats[$homeId]['played']++;
            $stats[$homeId]['score_for'] += $match->score_home;
            $stats[$homeId]['score_against'] += $match->score_away;

            $stats[$awayId]['played']++;
            $stats[$awayId]['score_for'] += $match->score_away;
            $stats[$awayId]['score_against'] += $match->score_home;

            if ($match->score_home > $match->score_away) {
                $stats[$homeId]['won']++;
                $stats[$awayId]['lost']++;
            } elseif ($match->score_home < $match->score_away) {
                $stats[$homeId]['lost']++;
                $stats[$awayId]['won']++;
            } else {
                $stats[$homeId]['drawn']++;
                $stats[$awayId]['drawn']++;
            }
        }

        $entries = [];

        foreach ($stats as $stat) {
            $scoreFor = $stat['score_for'];
            $scoreAgainst = $stat['score_against'];
            $difference = $scoreFor - $scoreAgainst;
            $points = ($stat['won'] * $winPoints)
                + ($stat['drawn'] * $drawPoints)
                + ($stat['lost'] * $lossPoints);

            $entries[] = new StandingsEntry(
                rank: 0,
                participantId: $stat['participant_id'],
                participantName: $stat['participant_name'],
                played: $stat['played'],
                won: $stat['won'],
                drawn: $stat['drawn'],
                lost: $stat['lost'],
                scoreFor: $scoreFor,
                scoreAgainst: $scoreAgainst,
                difference: $difference,
                points: $points,
            );
        }

        usort($entries, function (StandingsEntry $a, StandingsEntry $b) {
            return $b->points <=> $a->points
                ?: $b->difference <=> $a->difference
                ?: $b->scoreFor <=> $a->scoreFor
                ?: $b->won <=> $a->won;
        });

        $this->applyRanks($entries);

        return $entries;
    }

    /**
     * @param  list<StandingsEntry>  $entries
     */
    private function applyRanks(array &$entries): void
    {
        $count = count($entries);
        if ($count === 0) {
            return;
        }

        $currentRank = 1;
        $i = 0;

        while ($i < $count) {
            $j = $i;

            while ($j < $count && $this->isTied($entries[$i], $entries[$j])) {
                $j++;
            }

            for ($k = $i; $k < $j; $k++) {
                $entries[$k] = new StandingsEntry(
                    rank: $currentRank,
                    participantId: $entries[$k]->participantId,
                    participantName: $entries[$k]->participantName,
                    played: $entries[$k]->played,
                    won: $entries[$k]->won,
                    drawn: $entries[$k]->drawn,
                    lost: $entries[$k]->lost,
                    scoreFor: $entries[$k]->scoreFor,
                    scoreAgainst: $entries[$k]->scoreAgainst,
                    difference: $entries[$k]->difference,
                    points: $entries[$k]->points,
                );
            }

            $currentRank = $j + 1;
            $i = $j;
        }
    }

    private function isTied(StandingsEntry $a, StandingsEntry $b): bool
    {
        return $a->points === $b->points
            && $a->difference === $b->difference
            && $a->scoreFor === $b->scoreFor
            && $a->won === $b->won;
    }
}
