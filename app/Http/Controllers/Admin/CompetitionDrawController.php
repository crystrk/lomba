<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompetitionStatus;
use App\Generators\DrawGenerator;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MatchScoreController;
use App\Models\Competition;
use App\Models\CompetitionMatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Response;

class CompetitionDrawController extends Controller
{
    public function show(Request $request, Competition $competition): Response
    {
        Gate::authorize('view', $competition);

        $competition->load(['participants' => fn ($q) => $q->orderBy('draw_position')->orderBy('name')]);

        $matches = $competition->matches()
            ->orderBy('round')
            ->orderBy('leg')
            ->orderBy('sequence')
            ->get()
            ->map(fn (CompetitionMatch $m) => [
                'id' => $m->id,
                'round' => $m->round,
                'leg' => $m->leg,
                'sequence' => $m->sequence,
                'home' => $m->homeParticipant?->only('id', 'name', 'short_name'),
                'away' => $m->awayParticipant?->only('id', 'name', 'short_name'),
                'status' => $m->status->value,
                'next_match_id' => $m->next_match_id,
                'next_slot' => $m->next_slot,
                'loser_next_match_id' => $m->loser_next_match_id,
                'loser_next_slot' => $m->loser_next_slot,
                'match_type' => $m->match_type,
            ]);

        return Inertia('Admin/Competitions/Draw', [
            'competition' => $competition->only('id', 'name', 'slug', 'format', 'status', 'draw_version'),
            'participants' => $competition->participants,
            'matches' => $matches,
        ]);
    }

    public function shuffle(Request $request, Competition $competition): RedirectResponse
    {
        Gate::authorize('update', $competition);

        abort_unless($competition->isEditable(), 422, 'Competition is not editable.');

        $participantIds = $competition->participants()
            ->orderBy('draw_position')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        abort_if(count($participantIds) < 2, 422, 'At least two participants are required to draw.');

        if ($competition->isGroupFinalFour()) {
            abort_if(count($participantIds) <= 4 || count($participantIds) % 2 !== 0, 422, 'Format Group Final Four memerlukan minimal 6 tim dan jumlah tim harus genap (6, 8, 10, dst.).');
        }

        $shuffled = $participantIds;
        if (count($shuffled) > 2) {
            $maxAttempts = 5;
            do {
                shuffle($shuffled);
                $maxAttempts--;
            } while ($shuffled === $participantIds && $maxAttempts > 0);
        } else {
            shuffle($shuffled);
        }

        $result = DrawGenerator::generate($competition->format, $shuffled);

        DB::transaction(function () use ($competition, $result, $shuffled) {
            $competition->matches()->delete();

            $created = collect();

            foreach ($result->slots as $slot) {
                $created->push($competition->matches()->create([
                    'round' => $slot->round,
                    'leg' => $slot->leg,
                    'sequence' => $slot->sequence,
                    'participant_id_home' => $slot->homeId,
                    'participant_id_away' => $slot->awayId,
                    'status' => $slot->status,
                    'next_match_id' => null,
                    'next_slot' => $slot->nextSlot,
                    'loser_next_match_id' => null,
                    'loser_next_slot' => $slot->loserNextSlot,
                    'match_type' => $slot->matchType,
                ]));
            }

            foreach ($result->slots as $i => $slot) {
                $updatePayload = [];

                if ($slot->nextMatchId !== null) {
                    $target = $created->firstWhere('sequence', $slot->nextMatchId);
                    if ($target !== null) {
                        $updatePayload['next_match_id'] = $target->id;
                    }
                }

                if ($slot->loserNextMatchId !== null) {
                    $loserTarget = $created->firstWhere('sequence', $slot->loserNextMatchId);
                    if ($loserTarget !== null) {
                        $updatePayload['loser_next_match_id'] = $loserTarget->id;
                    }
                }

                if (! empty($updatePayload)) {
                    $created[$i]->update($updatePayload);
                }
            }

            foreach ($shuffled as $position => $participantId) {
                $competition->participants()
                    ->where('id', $participantId)
                    ->update(['draw_position' => $position + 1]);
            }

            $competition->update([
                'status' => CompetitionStatus::Drawn,
                'draw_version' => $competition->draw_version + 1,
            ]);
        });

        return redirect()->route('admin.competitions.draw.show', $competition)
            ->with('success', 'Undian berhasil dilakukan.');
    }

    public function reorder(Request $request, Competition $competition): RedirectResponse
    {
        Gate::authorize('update', $competition);

        abort_unless($competition->isEditable(), 422, 'Competition is not editable.');

        $participantIds = $competition->participants()
            ->pluck('id')
            ->toArray();

        $count = count($participantIds);
        abort_if($count < 2, 422, 'At least two participants are required to draw.');

        if ($competition->isGroupFinalFour()) {
            abort_if($count <= 4 || $count % 2 !== 0, 422, 'Format Group Final Four memerlukan minimal 6 tim dan jumlah tim harus genap (6, 8, 10, dst.).');
        }

        $request->validate([
            'participant_ids' => ['required', 'array', 'size:'.$count],
            'participant_ids.*' => ['required', 'integer', 'distinct', 'exists:participants,id'],
        ]);

        $orderedIds = $request->input('participant_ids');

        $diff = array_diff($orderedIds, $participantIds);
        abort_if(count($diff) > 0, 422, 'Provided participants do not match competition participants.');

        $result = DrawGenerator::generate($competition->format, $orderedIds);

        DB::transaction(function () use ($competition, $result, $orderedIds) {
            $competition->matches()->delete();

            $created = collect();

            foreach ($result->slots as $slot) {
                $created->push($competition->matches()->create([
                    'round' => $slot->round,
                    'leg' => $slot->leg,
                    'sequence' => $slot->sequence,
                    'participant_id_home' => $slot->homeId,
                    'participant_id_away' => $slot->awayId,
                    'status' => $slot->status,
                    'next_match_id' => null,
                    'next_slot' => $slot->nextSlot,
                    'loser_next_match_id' => null,
                    'loser_next_slot' => $slot->loserNextSlot,
                    'match_type' => $slot->matchType,
                ]));
            }

            foreach ($result->slots as $i => $slot) {
                $updatePayload = [];

                if ($slot->nextMatchId !== null) {
                    $target = $created->firstWhere('sequence', $slot->nextMatchId);
                    if ($target !== null) {
                        $updatePayload['next_match_id'] = $target->id;
                    }
                }

                if ($slot->loserNextMatchId !== null) {
                    $loserTarget = $created->firstWhere('sequence', $slot->loserNextMatchId);
                    if ($loserTarget !== null) {
                        $updatePayload['loser_next_match_id'] = $loserTarget->id;
                    }
                }

                if (! empty($updatePayload)) {
                    $created[$i]->update($updatePayload);
                }
            }

            foreach ($orderedIds as $position => $participantId) {
                $competition->participants()
                    ->where('id', $participantId)
                    ->update(['draw_position' => $position + 1]);
            }

            $competition->update([
                'status' => CompetitionStatus::Drawn,
                'draw_version' => $competition->draw_version + 1,
            ]);
        });

        return redirect()->route('admin.competitions.draw.show', $competition)
            ->with('success', 'Urutan undian berhasil diperbarui.');
    }

    public function lock(Request $request, Competition $competition): RedirectResponse
    {
        Gate::authorize('update', $competition);

        abort_unless($competition->isDrawn(), 422, 'Competition must be in drawn status to lock.');

        if ($competition->isGroupFinalFour()) {
            $participantCount = $competition->participants()->count();
            abort_if($participantCount <= 4 || $participantCount % 2 !== 0, 422, 'Format Group Final Four memerlukan minimal 6 tim dan jumlah tim harus genap (6, 8, 10, dst.).');
        }

        $matchCount = $competition->matches()->count();
        abort_if($matchCount === 0, 422, 'Cannot lock a competition with no matches.');

        $request->validate([
            'draw_version' => ['required', 'integer', 'in:'.$competition->draw_version],
        ]);

        $competition->update([
            'status' => CompetitionStatus::Locked,
            'locked_by' => $request->user()->id,
            'locked_at' => now(),
        ]);

        return redirect()->route('admin.competitions.draw.show', $competition)
            ->with('success', 'Undian berhasil dikunci.');
    }

    public function adjustGroupRank(Request $request, Competition $competition, MatchScoreController $matchScoreController): RedirectResponse
    {
        Gate::authorize('update', $competition);

        $request->validate([
            'participant_id_1' => ['required', 'integer', 'exists:participants,id'],
            'participant_id_2' => ['required', 'integer', 'exists:participants,id'],
        ]);

        $p1 = $competition->participants()->findOrFail($request->input('participant_id_1'));
        $p2 = $competition->participants()->findOrFail($request->input('participant_id_2'));

        $pos1 = $p1->draw_position;
        $pos2 = $p2->draw_position;

        DB::transaction(function () use ($p1, $p2, $pos1, $pos2, $competition, $matchScoreController) {
            $p1->updateQuietly(['draw_position' => $pos2]);
            $p2->updateQuietly(['draw_position' => $pos1]);

            if ($competition->isGroupFinalFour()) {
                $matchScoreController->checkAndPopulateGroupFinalFours($competition);
            }
        });

        return redirect()->back()->with('success', 'Urutan peringkat manual berhasil diperbarui.');
    }
}
