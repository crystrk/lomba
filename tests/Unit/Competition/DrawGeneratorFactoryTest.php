<?php

use App\Enums\CompetitionFormat;
use App\Generators\DrawGenerator;
use App\Generators\FullCompetitionGenerator;
use App\Generators\HalfCompetitionGenerator;
use App\Generators\KnockoutGenerator;
use App\Values\DrawResult;

it('returns half competition generator for half_competition format', function () {
    $generator = DrawGenerator::forFormat(CompetitionFormat::HalfCompetition);
    expect($generator)->toBeInstanceOf(HalfCompetitionGenerator::class);
});

it('returns full competition generator for full_competition format', function () {
    $generator = DrawGenerator::forFormat(CompetitionFormat::FullCompetition);
    expect($generator)->toBeInstanceOf(FullCompetitionGenerator::class);
});

it('returns knockout generator for knockout format', function () {
    $generator = DrawGenerator::forFormat(CompetitionFormat::Knockout);
    expect($generator)->toBeInstanceOf(KnockoutGenerator::class);
});

it('generate returns a DrawResult', function () {
    $result = DrawGenerator::generate(CompetitionFormat::HalfCompetition, [1, 2, 3, 4]);
    expect($result)->toBeInstanceOf(DrawResult::class);
});

it('generate with insufficient participants returns empty result', function () {
    $result = DrawGenerator::generate(CompetitionFormat::Knockout, [1]);
    expect($result->slots)->toBe([]);
});
