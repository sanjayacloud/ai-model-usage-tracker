<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Events;

use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;
use Illuminate\Foundation\Events\Dispatchable;

class UsageRecorded
{
    use Dispatchable;

    public function __construct(public UsageRecord $record) {}
}
