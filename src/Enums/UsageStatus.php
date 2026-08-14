<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Enums;

enum UsageStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
}
