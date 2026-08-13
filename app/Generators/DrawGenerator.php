<?php

namespace App\Generators;

use App\Enums\CompetitionFormat;
use App\Values\DrawResult;

class DrawGenerator
{
    private static ?HalfCompetitionGenerator $half = null;

    private static ?FullCompetitionGenerator $full = null;

    private static ?KnockoutGenerator $knockout = null;

    private static ?FinalFourGenerator $finalFour = null;

    public static function forFormat(CompetitionFormat $format): MatchGenerator
    {
        return match ($format) {
            CompetitionFormat::HalfCompetition => self::$half ??= new HalfCompetitionGenerator,
            CompetitionFormat::FullCompetition => self::$full ??= new FullCompetitionGenerator,
            CompetitionFormat::Knockout => self::$knockout ??= new KnockoutGenerator,
            CompetitionFormat::FinalFour => self::$finalFour ??= new FinalFourGenerator,
        };
    }

    public static function generate(CompetitionFormat $format, array $participantIds): DrawResult
    {
        return self::forFormat($format)->generate($participantIds);
    }
}
