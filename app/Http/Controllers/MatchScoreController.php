<?php

namespace App\Http\Controllers;

use App\Calculators\StandingsCalculator;
use App\Enums\CompetitionMatchStatus;
use App\Enums\CompetitionStatus;
use App\Http\Requests\MatchScoreRequest;
use App\Models\Competition;
use App\Models\CompetitionMatch;
use Illuminate\Support\Facades\DB;

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

        if ($competition->isKnockout()) {
            return $this->updateKnockout($request, $competition, $match, $scoreHome, $scoreAway);
        }

        return $this->updateCompetition($request, $competition, $match, $scoreHome, $scoreAway);
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
            $match->update([
                'score_home' => $scoreHome,
                'score_away' => $scoreAway,
                'winner_id' => $winnerId,
                'status' => CompetitionMatchStatus::Completed,
                'result_version' => $match->result_version + 1,
                'result_updated_by' => $request->user()->id,
                'result_updated_at' => now(),
            ]);

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

    private function updateKnockout(
        MatchScoreRequest $request,
        Competition $competition,
        CompetitionMatch $match,
        int $scoreHome,
        int $scoreAway,
    ) {
        $winnerId = $this->determineKnockoutWinner($match, $scoreHome, $scoreAway, $request->input('winner_id'));

        DB::transaction(function () use ($competition, $match, $scoreHome, $scoreAway, $winnerId, $request) {
            $isDownstreamCompleted = $this->isDownstreamCompleted($match);

            if ($match->isCompleted() && $match->winner_id !== $winnerId && $isDownstreamCompleted) {
                abort(422, 'Cannot change winner because downstream match has already been completed.');
            }

            $wasAlreadyCompleted = $match->isCompleted();
            $previousWinnerId = $match->winner_id;

            $match->update([
                'score_home' => $scoreHome,
                'score_away' => $scoreAway,
                'winner_id' => $winnerId,
                'win_method' => $request->input('win_method'),
                'status' => CompetitionMatchStatus::Completed,
                'result_version' => $match->result_version + 1,
                'result_updated_by' => $request->user()->id,
                'result_updated_at' => now(),
            ]);

            if ($wasAlreadyCompleted && $previousWinnerId !== $winnerId) {
                $this->clearDownstreamSlots($match);
            }

            $this->advanceWinner($match, $winnerId);

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

        if ($nextMatch->isBye()) {
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

    private function clearDownstreamSlots(CompetitionMatch $match): void
    {
        if ($match->next_match_id === null || $match->next_slot === null) {
            return;
        }

        $nextMatch = $match->nextMatch;

        if ($nextMatch->isBye()) {
            return;
        }

        if ($match->next_slot === 1) {
            $nextMatch->update(['participant_id_home' => null]);
        } else {
            $nextMatch->update(['participant_id_away' => null]);
        }

        if ($nextMatch->isReady()) {
            $nextMatch->update(['status' => CompetitionMatchStatus::Pending]);
        }
    }

    private function isDownstreamCompleted(CompetitionMatch $match): bool
    {
        if ($match->next_match_id === null || $match->next_slot === null) {
            return false;
        }

        $nextMatch = $match->nextMatch;

        if ($nextMatch->isCompleted()) {
            return true;
        }

        return $this->isDownstreamCompleted($nextMatch);
    }

    private function updateCompetitionCompletionStatus(Competition $competition): void
    {
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
}
