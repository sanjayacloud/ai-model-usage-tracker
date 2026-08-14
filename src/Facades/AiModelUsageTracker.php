<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool enabled()
 * @method static void resolveTrackableUsing(\Closure $resolver)
 * @method static \AiModelUsageTracker\AiModelUsageTracker\PendingUsage for(\Illuminate\Database\Eloquent\Model $trackable)
 * @method static \AiModelUsageTracker\AiModelUsageTracker\PendingUsage track()
 * @method static \AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord|null record(\AiModelUsageTracker\AiModelUsageTracker\DataObjects\UsageData $data)
 * @method static \AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord persist(\AiModelUsageTracker\AiModelUsageTracker\DataObjects\UsageData $data)
 * @method static \AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord|null capturePrism(object $response, \Illuminate\Database\Eloquent\Model|null $trackable = null)
 * @method static \AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord|null forInvocation(string $invocationId)
 * @method static \AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord|null latest()
 *
 * @see \AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTracker
 */
class AiModelUsageTracker extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTracker::class;
    }
}
