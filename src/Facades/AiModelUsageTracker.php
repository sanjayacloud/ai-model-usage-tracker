<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTracker
 */
class AiModelUsageTracker extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTracker::class;
    }
}
