<?php

namespace App\Enums;

enum ReleaseStatus: string
{
    case Draft = 'draft';
    case Planned = 'planned';
    case Released = 'released';
    case Canceled = 'canceled';
}
