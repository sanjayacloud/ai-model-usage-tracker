<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Pricing;

use AiModelUsageTracker\AiModelUsageTracker\Pricing\Catalog\LiteLlmCatalog;
use AiModelUsageTracker\AiModelUsageTracker\Pricing\Catalog\OpenRouterCatalog;
use Illuminate\Contracts\Config\Repository as Config;

class CatalogManager
{
    public function __construct(
        protected Config $config,
        protected CatalogStore $store,
        protected LiteLlmCatalog $liteLlm,
        protected OpenRouterCatalog $openRouter,
    ) {}

    /**
     * Fetch LiteLLM first, then fill gaps from OpenRouter. Never throws.
     *
     * @return array{models: int, source: string, skipped: bool}
     */
    public function refresh(bool $force = false): array
    {
        if (! $this->enabled()) {
            return ['models' => $this->count($this->store->models()), 'source' => 'disabled', 'skipped' => true];
        }

        if (! $force && $this->store->hasFreshCache()) {
            return ['models' => $this->count($this->store->models()), 'source' => 'cache', 'skipped' => true];
        }

        $liteLlm = $this->liteLlm->fetch();
        $openRouter = $this->openRouter->fetch();
        $merged = $this->merge($liteLlm, $openRouter);

        if ($merged === []) {
            return ['models' => $this->count($this->store->models()), 'source' => 'unchanged', 'skipped' => true];
        }

        $this->store->put($merged, $this->ttlHours());

        $source = match (true) {
            $liteLlm !== [] && $openRouter !== [] => 'litellm+openrouter',
            $liteLlm !== [] => 'litellm',
            default => 'openrouter',
        };

        return ['models' => $this->count($merged), 'source' => $source, 'skipped' => false];
    }

    public function enabled(): bool
    {
        return (bool) $this->config->get('ai-model-usage-tracker.pricing.fetch.enabled', true);
    }

    protected function ttlHours(): int
    {
        return max(1, (int) $this->config->get('ai-model-usage-tracker.pricing.fetch.ttl_hours', 24));
    }

    /**
     * @param  array<string, array<string, array<string, float>>>  $primary
     * @param  array<string, array<string, array<string, float>>>  $fallback
     * @return array<string, array<string, array<string, float>>>
     */
    protected function merge(array $primary, array $fallback): array
    {
        foreach ($fallback as $provider => $models) {
            foreach ($models as $model => $rates) {
                if (! isset($primary[$provider][$model])) {
                    $primary[$provider][$model] = $rates;
                }
            }
        }

        return $primary;
    }

    /**
     * @param  array<string, array<string, array<string, float>>>  $models
     */
    protected function count(array $models): int
    {
        $count = 0;

        foreach ($models as $providerModels) {
            $count += count($providerModels);
        }

        return $count;
    }
}
