<?php

namespace App\Http\Requests;

use App\Models\Competition;
use App\Models\CompetitionMatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MatchScoreRequest extends FormRequest
{
    public Competition $competition;

    public CompetitionMatch $match;

    public function authorize(): bool
    {
        $this->competition = $this->route('competition');
        $this->match = $this->route('match');

        if (! $this->competition->relationLoaded('matches')) {
            $this->competition->load('matches');
        }

        return $this->user()->can('updateScore', $this->competition);
    }

    public function rules(): array
    {
        return [
            'score_home' => ['required', 'integer', 'min:0'],
            'score_away' => ['required', 'integer', 'min:0'],
            'winner_id' => ['nullable', 'integer', 'exists:participants,id'],
            'win_method' => ['nullable', 'string', 'max:255'],
            'result_version' => ['required', 'integer', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $match = $this->match;
                $competition = $this->competition;

                if ($match->competition_id !== $competition->id) {
                    $validator->errors()->add('match', 'Match does not belong to this competition.');

                    return;
                }

                if (! $match->hasBothParticipants()) {
                    $validator->errors()->add('match', 'Match does not have both participants assigned.');

                    return;
                }

                if ($match->isBye()) {
                    $validator->errors()->add('match', 'Bye matches cannot have scores.');

                    return;
                }

                if ($match->isPending()) {
                    $validator->errors()->add('match', 'Pending matches cannot have scores yet.');

                    return;
                }

                if (! $competition->isActive()) {
                    $validator->errors()->add('competition', 'Competition must be locked or in progress.');

                    return;
                }

                if ((int) $this->input('result_version') !== $match->result_version) {
                    $validator->errors()->add('result_version', 'This result has been updated by another user. Please refresh and try again.');
                }

                $rawWinnerId = $this->input('winner_id');
                $winnerId = ($rawWinnerId !== null && $rawWinnerId !== '') ? (int) $rawWinnerId : null;
                $scoreHome = (int) $this->input('score_home');
                $scoreAway = (int) $this->input('score_away');

                if ($scoreHome === $scoreAway) {
                    if ($competition->isKnockout() && $winnerId === null) {
                        $validator->errors()->add('winner_id', 'A tie-break winner is required when scores are equal in knockout format.');
                    }

                    if ($winnerId !== null && $winnerId !== (int) $match->participant_id_home && $winnerId !== (int) $match->participant_id_away) {
                        $validator->errors()->add('winner_id', 'Tie-break winner must be one of the match participants.');
                    }
                }
            },
        ];
    }
}
