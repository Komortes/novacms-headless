<?php

namespace App\Enums;

enum SummaryStatus: string
{
    case PENDING = 'pending';
    case GENERATING = 'generating';
    case READY = 'ready';
    case FAILED = 'failed';
}
