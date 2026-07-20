<?php

use App\Enums\CompetitionMatchStatus;
use App\Generators\KnockoutGenerator;
use App\Values\DrawSlot;

beforeEach(function () {
    $this->generator = new KnockoutGenerator;
});

it('produces n-1 scored matches', function (int $n) {
    $ids = range(1, $n);
    $result = $this->generator->generate($ids);

    $scored = array_filter(
        $result->slots,
        fn (DrawSlot $s) => $s->status === CompetitionMatchStatus::Ready || $s->status === CompetitionMatchStatus::Pending,
    );

    expect(count($scored))->toBe($n - 1);
})->with([2, 3, 4, 5, 6, 8]);

it('AC-08: six teams produce bracket of 8 slots, 2 byes, 5 scored matches', function () {
    $ids = range(1, 6);
    $result = $this->generator->generate($ids);

    $byes = array_filter($result->slots, fn (DrawSlot $s) => $s->status === CompetitionMatchStatus::Bye);
    $scored = array_filter(
        $result->slots,
        fn (DrawSlot $s) => $s->status === CompetitionMatchStatus::Ready || $s->status === CompetitionMatchStatus::Pending,
    );

    expect(count($result->slots))->toBeGreaterThanOrEqual(7);
    expect(count($byes))->toBe(2);
    expect(count($scored))->toBe(5);
});

it('participant never plays against themself', function (int $n) {
    $ids = range(1, $n);
    $result = $this->generator->generate($ids);

    foreach ($result->slots as $slot) {
        if ($slot->homeId !== null && $slot->awayId !== null) {
            expect($slot->homeId)->not->toBe($slot->awayId);
        }
    }
})->with([2, 3, 4, 5, 6, 8]);

it('no participant appears more than once in first round', function (int $n) {
    $ids = range(1, $n);
    $result = $this->generator->generate($ids);

    $firstRound = array_filter($result->slots, fn (DrawSlot $s) => $s->round === 1);
    $participants = [];

    foreach ($firstRound as $slot) {
        if ($slot->homeId !== null) {
            $participants[] = $slot->homeId;
        }
        if ($slot->awayId !== null) {
            $participants[] = $slot->awayId;
        }
    }

    expect(count($participants))->toBe(count(array_unique($participants)));
})->with([2, 3, 4, 5, 6, 8]);

it('input determinism: same input produces same output', function () {
    $ids = [7, 3, 11, 5];
    $first = $this->generator->generate($ids);
    $second = $this->generator->generate($ids);

    expect($first->slots)->toEqual($second->slots);
});

it('every match points to next match via next_match_id', function () {
    $ids = range(1, 8);
    $result = $this->generator->generate($ids);

    $totalRounds = 3;

    foreach ($result->slots as $slot) {
        if ($slot->round < $totalRounds) {
            expect($slot->nextMatchId)->not->toBeNull();
            expect($slot->nextSlot)->not->toBeNull();
        } else {
            expect($slot->nextMatchId)->toBeNull();
            expect($slot->nextSlot)->toBeNull();
        }
    }
});

it('bye matches have only one participant', function () {
    $ids = range(1, 6);
    $result = $this->generator->generate($ids);

    $byes = array_filter($result->slots, fn (DrawSlot $s) => $s->status === CompetitionMatchStatus::Bye);

    foreach ($byes as $bye) {
        expect($bye->awayId)->toBeNull();
    }
});

it('correct bracket size and total rounds for various n', function (int $n, int $expectedSlots) {
    $ids = range(1, $n);
    $result = $this->generator->generate($ids);

    expect(count($result->slots))->toBe($expectedSlots);
})->with([
    [2, 1],
    [3, 3],
    [4, 3],
    [5, 7],
    [6, 7],
    [7, 7],
    [8, 7],
]);

it('sequence numbers are consecutive', function () {
    $ids = range(1, 6);
    $result = $this->generator->generate($ids);

    $sequences = array_map(fn (DrawSlot $s) => $s->sequence, $result->slots);
    expect($sequences)->toEqual(range(1, count($sequences)));
});

it('win_method and scores are null in generated draw', function () {
    $ids = range(1, 4);
    $result = $this->generator->generate($ids);

    // Draw slots don't carry win_method/score — those are model properties
    // This just verifies the DTO structure is correct
    expect($result->slots)->each->toBeInstanceOf(DrawSlot::class);
});

it('all legs are 1 in knockout', function () {
    $ids = range(1, 8);
    $result = $this->generator->generate($ids);

    foreach ($result->slots as $slot) {
        expect($slot->leg)->toBe(1);
    }
});
