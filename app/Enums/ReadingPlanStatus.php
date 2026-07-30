<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case FINISHED = 'finished';
    case EXPIRED = 'expired';
}
