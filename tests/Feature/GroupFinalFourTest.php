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

test('admin can create a group final four competition', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $response = $this->actingAs($admin)->post(route('admin.competitions.store'), [
        'name' => 'Turnamen Group Final Four',
        'sport' => CompetitionSport::General->value,
        'format' => CompetitionFormat::GroupFinalFour->value,
        'win_points' => 3,
        'draw_points' => 1,
        'loss_points' => 0,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('competitions', [
        'name' => 'Turnamen Group Final Four',
        'format' => CompetitionFormat::GroupFinalFour->value,
        'status' => CompetitionStatus::Draft->value,
        'win_points' => 3,
        'draw_points' => 1,
        'loss_points' => 0,
    ]);
});

test('drawing fails when team count is 4 or odd for group final four', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::GroupFinalFour,
        'status' => CompetitionStatus::Draft,
    ]);

    Participant::factory()->count(4)->create(['competition_id' => $competition->id]);

    $response = $this->actingAs($admin)->post(route('admin.competitions.shuffle', $competition));
    $response->assertStatus(422);
});

test('drawing succeeds with 6 teams and generates 8 matches (6 group + 2 finals)', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::GroupFinalFour,
        'status' => CompetitionStatus::Draft,
        'win_points' => 3,
        'draw_points' => 1,
        'loss_points' => 0,
    ]);

    $teams = [
        'BPMP A', 'Kantor Bahasa A', 'KGTK A',
        'BPMP B', 'Kantor Bahasa B', 'KGTK B',
    ];

    foreach ($teams as $name) {
        Participant::factory()->create(['competition_id' => $competition->id, 'name' => $name]);
    }

    $response = $this->actingAs($admin)->post(route('admin.competitions.shuffle', $competition));
    $response->assertRedirect();

    $competition->refresh();
    expect($competition->status)->toBe(CompetitionStatus::Drawn);
    expect($competition->matches)->toHaveCount(8);

    $groupAMatches = $competition->matches()->where('match_type', 'group_a')->get();
    $groupBMatches = $competition->matches()->where('match_type', 'group_b')->get();
    $finalMatch = $competition->matches()->where('match_type', 'final')->first();
    $thirdPlaceMatch = $competition->matches()->where('match_type', 'third_place')->first();

    expect($groupAMatches)->toHaveCount(3);
    expect($groupBMatches)->toHaveCount(3);
    expect($finalMatch)->not->toBeNull();
    expect($thirdPlaceMatch)->not->toBeNull();

    expect($finalMatch->participant_id_home)->toBeNull();
    expect($finalMatch->participant_id_away)->toBeNull();
    expect($thirdPlaceMatch->participant_id_home)->toBeNull();
    expect($thirdPlaceMatch->participant_id_away)->toBeNull();
});

test('completing group stage matches automatically populates final 1-2 and final 3-4 matches', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::GroupFinalFour,
        'status' => CompetitionStatus::Draft,
        'win_points' => 3,
        'draw_points' => 1,
        'loss_points' => 0,
    ]);

    $p1 = Participant::factory()->create(['competition_id' => $competition->id, 'name' => 'BPMP A', 'draw_position' => 1]);
    $p2 = Participant::factory()->create(['competition_id' => $competition->id, 'name' => 'Kantor Bahasa A', 'draw_position' => 2]);
    $p3 = Participant::factory()->create(['competition_id' => $competition->id, 'name' => 'KGTK A', 'draw_position' => 3]);

    $p4 = Participant::factory()->create(['competition_id' => $competition->id, 'name' => 'BPMP B', 'draw_position' => 4]);
    $p5 = Participant::factory()->create(['competition_id' => $competition->id, 'name' => 'Kantor Bahasa B', 'draw_position' => 5]);
    $p6 = Participant::factory()->create(['competition_id' => $competition->id, 'name' => 'KGTK B', 'draw_position' => 6]);

    $this->actingAs($admin)->post(route('admin.competitions.shuffle', $competition));
    $this->actingAs($admin)->post(route('admin.competitions.lock', $competition), [
        'draw_version' => $competition->fresh()->draw_version,
    ]);

    $competition->refresh();

    // Input scores for 6 group stage matches
    $groupMatches = $competition->matches()->whereIn('match_type', ['group_a', 'group_b'])->get();
    foreach ($groupMatches as $m) {
        $this->actingAs($admin)->post(route('admin.matches.score.update', [$competition, $m]), [
            'score_home' => 2,
            'score_away' => 0,
            'result_version' => $m->result_version,
        ]);
    }

    $finalMatch = $competition->matches()->where('match_type', 'final')->first();
    $thirdPlaceMatch = $competition->matches()->where('match_type', 'third_place')->first();

    expect($finalMatch->participant_id_home)->not->toBeNull();
    expect($finalMatch->participant_id_away)->not->toBeNull();
    expect($finalMatch->status)->toBe(CompetitionMatchStatus::Ready);

    expect($thirdPlaceMatch->participant_id_home)->not->toBeNull();
    expect($thirdPlaceMatch->participant_id_away)->not->toBeNull();
    expect($thirdPlaceMatch->status)->toBe(CompetitionMatchStatus::Ready);
});
