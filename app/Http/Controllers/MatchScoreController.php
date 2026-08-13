<?php

namespace App\Http\Controllers;

use App\Calculators\StandingsCalculator;
use App\Enums\CompetitionMatchStatus;
use App\Enums\CompetitionStatus;
use App\Http\Requests\MatchScoreRequest;
use App\Models\Competition;
use App\Models\CompetitionMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MatchScoreController extends Controller
{
    public function __construct(
        private readonly StandingsCalculator $standingsCalculator,
    ) {}

    public function update(MatchScoreRequest $request, Competition $competition, CompetitionMatch $match)
    {
        $data = $request->validated();

        $scoreHome = (int) $data['score_home'];
        $scoreAway = (int) $data['score_away'];

        if ($competition->isKnockout() || ($competition->isGroupFinalFour() && in_array($match->match_type, ['final', 'third_place'], true))) {
            return $this->updateKnockout($request, $competition, $match, $scoreHome, $scoreAway);
        }

        $response = $this->updateCompetition($request, $competition, $match, $scoreHome, $scoreAway);

        if ($competition->isGroupFinalFour()) {
            $this->checkAndPopulateGroupFinalFours($competition);
        }

        return $response;
    }

    private function updateCompetition(
        MatchScoreRequest $request,
        Competition $competition,
        CompetitionMatch $match,
        int $scoreHome,
        int $scoreAway,
    ) {
        $winnerId = $scoreHome > $scoreAway
            ? $match->participant_id_home
            : ($scoreHome < $scoreAway ? $match->participant_id_away : null);

        DB::transaction(function () use ($competition, $match, $scoreHome, $scoreAway, $winnerId, $request) {
            $updateData = [
                'score_home' => $scoreHome,
                'score_away' => $scoreAway,
                'winner_id' => $winnerId,
                'status' => CompetitionMatchStatus::Completed,
                'result_version' => $match->result_version + 1,
                'result_updated_by' => $request->user()->id,
                'result_updated_at' => now(),
            ];

            if ($request->has('scheduled_time')) {
                $updateData['scheduled_time'] = $request->input('scheduled_time');
            }

            $match->update($updateData);

            if ($competition->isLocked()) {
                $competition->update(['status' => CompetitionStatus::InProgress]);
            }

            $this->updateCompetitionCompletionStatus($competition);
        });

        $standings = $this->standingsCalculator->calculate(
            $competition,
            $competition->participants,
            $competition->matches()->where('status', CompetitionMatchStatus::Completed)->get(),
        );

        return redirect()->back()->with('success', 'Score updated.')->with('standings', $standings);
    }

    public function updateSchedule(Request $request, Competition $competition, CompetitionMatch $match)
    {
        Gate::authorize('updateScore', $competition);

        abort_if($match->competition_id !== $competition->id, 422, 'Match does not belong to this competition.');
        abort_if($competition->isResultsLocked(), 422, 'Hasil pertandingan telah dikunci final.');

        $request->validate([
            'scheduled_time' => ['nullable', 'string', 'max:100'],
        ]);

        $match->update([
            'scheduled_time' => $request->input('scheduled_time'),
        ]);

        return redirect()->back()->with('success', 'Jam pertandingan berhasil diperbarui.');
    }

    private function updateKnockout(
        MatchScoreRequest $request,
        Competition $competition,
        CompetitionMatch $match,
        int $scoreHome,
        int $scoreAway,
    ) {
        $winnerId = $this->determineKnockoutWinner($match, $scoreHome, $scoreAway, $request->input('winner_id'));
        $loserId = ($winnerId === (int) $match->participant_id_home)
            ? (int) $match->participant_id_away
            : (int) $match->participant_id_home;

        DB::transaction(function () use ($competition, $match, $scoreHome, $scoreAway, $winnerId, $loserId, $request) {
            $isDownstreamCompleted = $this->isDownstreamCompleted($match);

            if ($match->isCompleted() && $match->winner_id !== $winnerId && $isDownstreamCompleted) {
                abort(422, 'Cannot change winner because downstream match has already been completed.');
            }

            $wasAlreadyCompleted = $match->isCompleted();
            $previousWinnerId = $match->winner_id;

            $updateData = [
                'score_home' => $scoreHome,
                'score_away' => $scoreAway,
                'winner_id' => $winnerId,
                'win_method' => $request->input('win_method'),
                'status' => CompetitionMatchStatus::Completed,
                'result_version' => $match->result_version + 1,
                'result_updated_by' => $request->user()->id,
                'result_updated_at' => now(),
            ];

            if ($request->has('scheduled_time')) {
                $updateData['scheduled_time'] = $request->input('scheduled_time');
            }

            $match->update($updateData);

            if ($wasAlreadyCompleted && $previousWinnerId !== $winnerId) {
                $this->clearDownstreamSlots($match);
            }

            $this->advanceWinner($match, $winnerId);
            $this->advanceLoser($match, $loserId);

            if ($competition->isLocked()) {
                $competition->update(['status' => CompetitionStatus::InProgress]);
            }

            $this->updateKnockoutCompletionStatus($competition);
        });

        return redirect()->back()->with('success', 'Score updated.');
    }

    private function determineKnockoutWinner(CompetitionMatch $match, int $scoreHome, int $scoreAway, mixed $submittedWinnerId): int
    {
        if ($scoreHome > $scoreAway) {
            return (int) $match->participant_id_home;
        }

        if ($scoreHome < $scoreAway) {
            return (int) $match->participant_id_away;
        }

        return (int) $submittedWinnerId;
    }

    private function advanceWinner(CompetitionMatch $match, int $winnerId): void
    {
        if ($match->next_match_id === null || $match->next_slot === null) {
            return;
        }

        $nextMatch = $match->nextMatch;

        if (! $nextMatch || $nextMatch->isBye()) {
            return;
        }

        if ($match->next_slot === 1) {
            $nextMatch->update(['participant_id_home' => $winnerId]);
        } else {
            $nextMatch->update(['participant_id_away' => $winnerId]);
        }

        if ($nextMatch->hasBothParticipants() && $nextMatch->isPending()) {
            $nextMatch->update(['status' => CompetitionMatchStatus::Ready]);
        }
    }

    private function advanceLoser(CompetitionMatch $match, int $loserId): void
    {
        if ($match->loser_next_match_id === null || $match->loser_next_slot === null) {
            return;
        }

        $loserNextMatch = $match->loserNextMatch;

        if (! $loserNextMatch || $loserNextMatch->isBye()) {
            return;
        }

        if ($match->loser_next_slot === 1) {
            $loserNextMatch->update(['participant_id_home' => $loserId]);
        } else {
            $loserNextMatch->update(['participant_id_away' => $loserId]);
        }

        if ($loserNextMatch->hasBothParticipants() && $loserNextMatch->isPending()) {
            $loserNextMatch->update(['status' => CompetitionMatchStatus::Ready]);
        }
    }

    private function clearDownstreamSlots(CompetitionMatch $match): void
    {
        if ($match->next_match_id !== null && $match->next_slot !== null) {
            $nextMatch = $match->nextMatch;

            if ($nextMatch && ! $nextMatch->isBye()) {
                if ($match->next_slot === 1) {
                    $nextMatch->update(['participant_id_home' => null]);
                } else {
                    $nextMatch->update(['participant_id_away' => null]);
                }

                if ($nextMatch->isReady()) {
                    $nextMatch->update(['status' => CompetitionMatchStatus::Pending]);
                }
            }
        }

        if ($match->loser_next_match_id !== null && $match->loser_next_slot !== null) {
            $loserNextMatch = $match->loserNextMatch;

            if ($loserNextMatch && ! $loserNextMatch->isBye()) {
                if ($match->loser_next_slot === 1) {
                    $loserNextMatch->update(['participant_id_home' => null]);
                } else {
                    $loserNextMatch->update(['participant_id_away' => null]);
                }

                if ($loserNextMatch->isReady()) {
                    $loserNextMatch->update(['status' => CompetitionMatchStatus::Pending]);
                }
            }
        }
    }

    private function isDownstreamCompleted(CompetitionMatch $match): bool
    {
        if ($match->next_match_id !== null && $match->next_slot !== null) {
            $nextMatch = $match->nextMatch;

            if ($nextMatch && ($nextMatch->isCompleted() || $this->isDownstreamCompleted($nextMatch))) {
                return true;
            }
        }

        if ($match->loser_next_match_id !== null && $match->loser_next_slot !== null) {
            $loserNextMatch = $match->loserNextMatch;

            if ($loserNextMatch && ($loserNextMatch->isCompleted() || $this->isDownstreamCompleted($loserNextMatch))) {
                return true;
            }
        }

        return false;
    }

    private function updateCompetitionCompletionStatus(Competition $competition): void
    {
        if ($competition->isGroupFinalFour()) {
            $totalCount = $competition->matches()->count();
            $completedCount = $competition->matches()
                ->where('status', CompetitionMatchStatus::Completed)
                ->count();

            if ($totalCount > 0 && $completedCount >= $totalCount) {
                $competition->update(['status' => CompetitionStatus::Completed]);
            } else {
                $competition->update(['status' => CompetitionStatus::InProgress]);
            }

            return;
        }

        $scorableMatchCount = $competition->matches()
            ->whereIn('status', [CompetitionMatchStatus::Pending, CompetitionMatchStatus::Ready, CompetitionMatchStatus::Completed])
            ->where(function ($q) {
                $q->whereNotNull('participant_id_home')
                    ->whereNotNull('participant_id_away');
            })
            ->count();

        $completedScorableCount = $competition->matches()
            ->where('status', CompetitionMatchStatus::Completed)
            ->where(function ($q) {
                $q->whereNotNull('participant_id_home')
                    ->whereNotNull('participant_id_away');
            })
            ->count();

        if ($scorableMatchCount > 0 && $completedScorableCount >= $scorableMatchCount) {
            $competition->update(['status' => CompetitionStatus::Completed]);
        }
    }

    private function updateKnockoutCompletionStatus(Competition $competition): void
    {
        $nonByeMatches = $competition->matches()
            ->where('status', '!=', CompetitionMatchStatus::Bye)
            ->whereNotNull('participant_id_home')
            ->whereNotNull('participant_id_away')
            ->count();

        $completedNonBye = $competition->matches()
            ->where('status', CompetitionMatchStatus::Completed)
            ->whereNotNull('participant_id_home')
            ->whereNotNull('participant_id_away')
            ->count();

        if ($nonByeMatches > 0 && $completedNonBye >= $nonByeMatches) {
            $competition->update(['status' => CompetitionStatus::Completed]);
        }
    }

    public function checkAndPopulateGroupFinalFours(Competition $competition): void
    {
        if (! $competition->isGroupFinalFour()) {
            return;
        }

        $groupMatches = $competition->matches()
            ->whereIn('match_type', ['group_a', 'group_b'])
            ->get();

        $totalGroupMatches = $groupMatches->count();
        $completedGroupMatches = $groupMatches->where('status', CompetitionMatchStatus::Completed)->count();

        $thirdPlaceMatch = $competition->matches()->where('match_type', 'third_place')->first();
        $finalMatch = $competition->matches()->where('match_type', 'final')->first();

        if ($totalGroupMatches > 0 && $completedGroupMatches === $totalGroupMatches) {
            $participants = $competition->participants()->orderBy('draw_position')->orderBy('id')->get();
            $halfCount = intdiv($participants->count(), 2);
            $groupAParticipants = $participants->take($halfCount);
            $groupBParticipants = $participants->skip($halfCount)->take($halfCount);

            $groupAMatches = $groupMatches->where('match_type', 'group_a');
            $groupBMatches = $groupMatches->where('match_type', 'group_b');

            $standingsA = $this->standingsCalculator->calculate($competition, $groupAParticipants, $groupAMatches);
            $standingsB = $this->standingsCalculator->calculate($competition, $groupBParticipants, $groupBMatches);

            if (count($standingsA) >= 2 && count($standingsB) >= 2) {
                $juaraA = $standingsA[0]->participantId;
                $runnerUpA = $standingsA[1]->participantId;
                $juaraB = $standingsB[0]->participantId;
                $runnerUpB = $standingsB[1]->participantId;

                if ($thirdPlaceMatch && ! $thirdPlaceMatch->isCompleted()) {
                    $thirdPlaceMatch->update([
                        'participant_id_home' => $runnerUpA,
                        'participant_id_away' => $runnerUpB,
                        'status' => CompetitionMatchStatus::Ready,
                    ]);
                }

                if ($finalMatch && ! $finalMatch->isCompleted()) {
                    $finalMatch->update([
                        'participant_id_home' => $juaraA,
                        'participant_id_away' => $juaraB,
                        'status' => CompetitionMatchStatus::Ready,
                    ]);
                }
            }
        } else {
            if ($thirdPlaceMatch && ! $thirdPlaceMatch->isCompleted()) {
                $thirdPlaceMatch->update([
                    'participant_id_home' => null,
                    'participant_id_away' => null,
                    'status' => CompetitionMatchStatus::Pending,
                ]);
            }

            if ($finalMatch && ! $finalMatch->isCompleted()) {
                $finalMatch->update([
                    'participant_id_home' => null,
                    'participant_id_away' => null,
                    'status' => CompetitionMatchStatus::Pending,
                ]);
            }
        }

        $this->updateCompetitionCompletionStatus($competition);
    }
}
