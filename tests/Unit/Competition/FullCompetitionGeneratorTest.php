<?php

use App\Generators\FullCompetitionGenerator;
use App\Values\DrawSlot;

beforeEach(function () {
    $this->generator = new FullCompetitionGenerator;
});

it('produces n(n-1) matches', function (int $n) {
    $ids = range(1, $n);
    $result = $this->generator->generate($ids);

    expect(count($result->slots))->toBe($n * ($n - 1));
})->with([2, 3, 4, 5, 6, 8]);

it('AC-07: four teams produce 12 matches in 6 rounds', function () {
    $ids = range(1, 4);
    $result = $this->generator->generate($ids);

    expect(count($result->slots))->toBe(12);

    $rounds = array_unique(array_map(fn (DrawSlot $s) => $s->round, $result->slots));
    expect($rounds)->toHaveCount(6);
});

it('second leg has reversed home/away from first leg', function () {
    $ids = range(1, 4);
    $result = $this->generator->generate($ids);

    $leg1 = array_values(array_filter($result->slots, fn (DrawSlot $s) => $s->leg === 1));
    $leg2 = array_values(array_filter($result->slots, fn (DrawSlot $s) => $s->leg === 2));

    expect(count($leg1))->toBe(count($leg2));

    foreach ($leg1 as $i => $slot) {
        $match = $leg2[$i];
        expect([$match->homeId, $match->awayId])->toBe([$slot->awayId, $slot->homeId]);
    }
});

it('each ordered pair (home, away) appears exactly once across two legs', function (int $n) {
    $ids = range(1, $n);
    $result = $this->generator->generate($ids);

    $orderedPairs = array_map(
        fn (DrawSlot $s) => "{$s->homeId}-{$s->awayId}",
        $result->slots,
    );

    expect(count($orderedPairs))->toBe($n * ($n - 1));
    expect(count(array_unique($orderedPairs)))->toBe(count($orderedPairs));
})->with([2, 3, 4, 5, 6]);

it('participant never plays against themself', function (int $n) {
    $ids = range(1, $n);
    $result = $this->generator->generate($ids);

    foreach ($result->slots as $slot) {
        expect($slot->homeId)->not->toBe($slot->awayId);
    }
})->with([2, 3, 4, 5, 6, 8]);

it('input determinism: same input produces same output', function () {
    $ids = [7, 3, 11, 5];
    $first = $this->generator->generate($ids);
    $second = $this->generator->generate($ids);

    expect($first->slots)->toEqual($second->slots);
});

it('round numbers of leg 2 do not overlap with leg 1', function () {
    $ids = range(1, 6);
    $result = $this->generator->generate($ids);

    $leg1Rounds = array_unique(array_map(fn (DrawSlot $s) => $s->round, array_filter(
        $result->slots, fn (DrawSlot $s) => $s->leg === 1,
    )));
    $leg2Rounds = array_unique(array_map(fn (DrawSlot $s) => $s->round, array_filter(
        $result->slots, fn (DrawSlot $s) => $s->leg === 2,
    )));

    foreach ($leg1Rounds as $r) {
        expect($leg2Rounds)->not->toContain($r);
    }
});

it('no next_match_id or next_slot for full competition', function () {
    $ids = range(1, 4);
    $result = $this->generator->generate($ids);

    foreach ($result->slots as $slot) {
        expect($slot->nextMatchId)->toBeNull();
        expect($slot->nextSlot)->toBeNull();
    }
});
