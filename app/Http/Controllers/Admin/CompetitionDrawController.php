<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompetitionStatus;
use App\Generators\DrawGenerator;
use App\Http\Controllers\Controller;
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
                ]));
            }

            foreach ($result->slots as $i => $slot) {
                if ($slot->nextMatchId === null) {
                    continue;
                }

                $target = $created->firstWhere('sequence', $slot->nextMatchId);
                if ($target === null) {
                    $target = $created->where('round', $slot->round + 1)->first();
                }

                if ($target !== null) {
                    $created[$i]->update(['next_match_id' => $target->id]);
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

    public function lock(Request $request, Competition $competition): RedirectResponse
    {
        Gate::authorize('update', $competition);

        abort_unless($competition->isDrawn(), 422, 'Competition must be in drawn status to lock.');

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
}
