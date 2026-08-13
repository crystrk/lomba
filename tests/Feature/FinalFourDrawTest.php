<?php

use App\Enums\CompetitionFormat;
use App\Enums\CompetitionMatchStatus;
use App\Enums\CompetitionSport;
use App\Enums\CompetitionStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can create a final four competition without points', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $response = $this->actingAs($admin)->post(route('admin.competitions.store'), [
        'name' => 'Turnamen Final Four',
        'sport' => CompetitionSport::General->value,
        'format' => CompetitionFormat::FinalFour->value,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('competitions', [
        'name' => 'Turnamen Final Four',
        'format' => CompetitionFormat::FinalFour->value,
        'status' => CompetitionStatus::Draft->value,
        'win_points' => null,
        'draw_points' => null,
        'loss_points' => null,
    ]);
});

test('draw generator creates semifinals, final, and 3rd place match for final four with 4 teams', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::FinalFour,
        'status' => CompetitionStatus::Draft,
    ]);

    $teamA = Participant::factory()->create(['competition_id' => $competition->id, 'name' => 'KGTK A']);
    $teamB = Participant::factory()->create(['competition_id' => $competition->id, 'name' => 'BPMP']);
    $teamC = Participant::factory()->create(['competition_id' => $competition->id, 'name' => 'Kantor Bahasa']);
    $teamD = Participant::factory()->create(['competition_id' => $competition->id, 'name' => 'KGTK B']);

    $response = $this->actingAs($admin)->post(route('admin.competitions.shuffle', $competition));
    $response->assertRedirect();

    $competition->refresh();
    expect($competition->status)->toBe(CompetitionStatus::Drawn);
    expect($competition->matches)->toHaveCount(4);

    $finalMatch = $competition->matches()->where('match_type', 'final')->first();
    $thirdPlaceMatch = $competition->matches()->where('match_type', 'third_place')->first();
    $semifinals = $competition->matches()->where('match_type', 'semifinal')->get();

    expect($finalMatch)->not->toBeNull();
    expect($thirdPlaceMatch)->not->toBeNull();
    expect($semifinals)->toHaveCount(2);

    foreach ($semifinals as $sf) {
        expect($sf->next_match_id)->toBe($finalMatch->id);
        expect($sf->loser_next_match_id)->toBe($thirdPlaceMatch->id);
    }
});

test('semifinal winners advance to final and losers advance to 3rd place match', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::FinalFour,
        'status' => CompetitionStatus::Draft,
    ]);

    $p1 = Participant::factory()->create(['competition_id' => $competition->id, 'name' => 'Tim 1']);
    $p2 = Participant::factory()->create(['competition_id' => $competition->id, 'name' => 'Tim 2']);
    $p3 = Participant::factory()->create(['competition_id' => $competition->id, 'name' => 'Tim 3']);
    $p4 = Participant::factory()->create(['competition_id' => $competition->id, 'name' => 'Tim 4']);

    $this->actingAs($admin)->post(route('admin.competitions.shuffle', $competition));
    $this->actingAs($admin)->post(route('admin.competitions.lock', $competition), [
        'draw_version' => $competition->fresh()->draw_version,
    ]);

    $competition->refresh();
    $sf1 = $competition->matches()->where('sequence', 1)->first();
    $sf2 = $competition->matches()->where('sequence', 2)->first();
    $final = $competition->matches()->where('match_type', 'final')->first();
    $thirdPlace = $competition->matches()->where('match_type', 'third_place')->first();

    // SF 1: Tim 1 vs Tim 2 (Tim 1 wins 2-1)
    $this->actingAs($admin)->post(route('admin.matches.score.update', [$competition, $sf1]), [
        'score_home' => 2,
        'score_away' => 1,
        'result_version' => $sf1->result_version,
    ]);

    $final->refresh();
    $thirdPlace->refresh();

    expect($final->participant_id_home)->toBe($sf1->participant_id_home);
    expect($thirdPlace->participant_id_home)->toBe($sf1->participant_id_away);

    // SF 2: Tim 3 vs Tim 4 (Tim 4 wins 0-3)
    $this->actingAs($admin)->post(route('admin.matches.score.update', [$competition, $sf2]), [
        'score_home' => 0,
        'score_away' => 3,
        'result_version' => $sf2->result_version,
    ]);

    $final->refresh();
    $thirdPlace->refresh();

    expect($final->participant_id_away)->toBe($sf2->participant_id_away);
    expect($thirdPlace->participant_id_away)->toBe($sf2->participant_id_home);

    expect($final->status)->toBe(CompetitionMatchStatus::Ready);
    expect($thirdPlace->status)->toBe(CompetitionMatchStatus::Ready);
});
