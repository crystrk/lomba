<?php

namespace App\Enums;

enum CompetitionFormat: string
{
    case Knockout = 'knockout';
    case FullCompetition = 'full_competition';
    case HalfCompetition = 'half_competition';
    case FinalFour = 'final_four';
    case GroupFinalFour = 'group_final_four';
}
