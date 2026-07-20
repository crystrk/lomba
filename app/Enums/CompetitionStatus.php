<?php

namespace App\Enums;

enum CompetitionStatus: string
{
    case Draft = 'draft';
    case Drawn = 'drawn';
    case Locked = 'locked';
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
