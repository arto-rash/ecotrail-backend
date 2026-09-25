<?php

namespace App\Enums;

enum TrailStatus: string
{
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
}
