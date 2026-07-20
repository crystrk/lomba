<?php

namespace App\Enums;

enum CompetitionMatchStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Completed = 'completed';
    case Bye = 'bye';
}
