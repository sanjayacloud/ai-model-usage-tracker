<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Instrumentation;

use AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTracker;
use AiModelUsageTracker\AiModelUsageTracker\Contracts\UsageInstrumentation;
use AiModelUsageTracker\AiModelUsageTracker\DataObjects\UsageData;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Driver;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Operation;
use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Model;
use Prism\Prism\Prism;

/**
 * Bridges Prism (prism-php/prism) responses into usage records.
 *
 * Prism has no global usage event, so callers pass the response object to
 * capture() (e.g. via AiModelUsageTracker::capturePrism($response)).
 */
class PrismInstrumentation implements UsageInstrumentation
{
    public function __construct(
        protected Config $config,
        protected AiModelUsageTracker $tracker,
    ) {}

    public function shouldRegister(): bool
    {
        return (bool) $this->config->get('ai-model-usage-tracker.instrumentation.prism', false)
            && class_exists(Prism::class);
    }

    public function register(): void
    {
        // Prism exposes no global event to hook; usage is captured on demand
        // through capture().
    }

    /**
     * Map a Prism response object (with ->usage and ->meta) to a usage record.
     */
    public function capture(object $response, ?Model $trackable = null): ?UsageRecord
    {
        $usage = $response->usage ?? null;
        $meta = $response->meta ?? null;

        $data = new UsageData(
            driver: Driver::Prism,
            operation: Operation::Chat,
            provider: is_object($meta) && isset($meta->provider) ? (string) $meta->provider : null,
            model: is_object($meta) && isset($meta->model) ? (string) $meta->model : null,
            promptTokens: (int) ($usage->promptTokens ?? 0),
            completionTokens: (int) ($usage->completionTokens ?? 0),
            trackableType: $trackable?->getMorphClass(),
            trackableId: $trackable?->getKey(),
        );

        return $this->tracker->record($data);
    }
}
