<?php

use App\Generators\HalfCompetitionGenerator;
use App\Values\DrawSlot;

beforeEach(function () {
    $this->generator = new HalfCompetitionGenerator;
});

it('produces n(n-1)/2 matches', function (int $n) {
    $ids = range(1, $n);
    $result = $this->generator->generate($ids);

    expect(count($result->slots))->toBe($n * ($n - 1) / 2);
})->with([2, 3, 4, 5, 6, 8]);

it('produces n-1 rounds for even participant count', function () {
    $ids = range(1, 4);
    $result = $this->generator->generate($ids);

    $rounds = array_unique(array_map(fn (DrawSlot $s) => $s->round, $result->slots));
    expect($rounds)->toHaveCount(3);
});

it('produces n rounds for odd participant count', function () {
    $ids = range(1, 5);
    $result = $this->generator->generate($ids);

    $rounds = array_unique(array_map(fn (DrawSlot $s) => $s->round, $result->slots));
    expect($rounds)->toHaveCount(5);
});

it('AC-06: four teams produce 6 matches in 3 rounds', function () {
    $ids = range(1, 4);
    $result = $this->generator->generate($ids);

    expect(count($result->slots))->toBe(6);

    $rounds = array_unique(array_map(fn (DrawSlot $s) => $s->round, $result->slots));
    expect($rounds)->toHaveCount(3);
});

it('each unordered pair appears exactly once', function (int $n) {
    $ids = range(1, $n);
    $result = $this->generator->generate($ids);

    $pairs = [];
    foreach ($result->slots as $slot) {
        $pair = [min($slot->homeId, $slot->awayId), max($slot->homeId, $slot->awayId)];
        $pairs[] = implode('-', $pair);
    }

    expect(count($pairs))->toBe($n * ($n - 1) / 2);
    expect(count(array_unique($pairs)))->toBe(count($pairs));
})->with([2, 3, 4, 5, 6, 8]);

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

it('each round has at most floor(n/2) matches', function (int $n) {
    $ids = range(1, $n);
    $result = $this->generator->generate($ids);

    $matchesPerRound = [];
    foreach ($result->slots as $slot) {
        $matchesPerRound[$slot->round][] = $slot;
    }

    $maxExpected = intdiv($n, 2);
    foreach ($matchesPerRound as $round => $matches) {
        expect(count($matches))->toBeLessThanOrEqual($maxExpected);
    }
})->with([2, 3, 4, 5, 6, 8]);

it('all legs are 1', function (int $n) {
    $ids = range(1, $n);
    $result = $this->generator->generate($ids);

    foreach ($result->slots as $slot) {
        expect($slot->leg)->toBe(1);
    }
})->with([2, 3, 4]);

it('sequence numbers are consecutive', function () {
    $ids = range(1, 4);
    $result = $this->generator->generate($ids);

    $sequences = array_map(fn (DrawSlot $s) => $s->sequence, $result->slots);
    expect($sequences)->toEqual(range(1, count($sequences)));
});

it('no next_match_id or next_slot for half competition', function () {
    $ids = range(1, 4);
    $result = $this->generator->generate($ids);

    foreach ($result->slots as $slot) {
        expect($slot->nextMatchId)->toBeNull();
        expect($slot->nextSlot)->toBeNull();
    }
});
