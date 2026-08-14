<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Pricing;

use Illuminate\Contracts\Config\Repository as Config;

class PricingRepository
{
    public function __construct(protected Config $config) {}

    public function currency(): string
    {
        return (string) $this->config->get('ai-model-usage-tracker.pricing.currency', 'USD');
    }

    /**
     * Resolve per-1M-token rates for a provider/model pair.
     *
     * When the provider is unknown the model is searched across all providers.
     *
     * @return array{input: float, output: float, cache_write: float, cache_read: float, reasoning: float}|null
     */
    public function ratesFor(?string $provider, ?string $model): ?array
    {
        if ($model === null) {
            return null;
        }

        /** @var array<string, array<string, array<string, float>>> $models */
        $models = $this->config->get('ai-model-usage-tracker.pricing.models', []);

        if ($provider !== null && isset($models[$provider][$model])) {
            return $this->normalize($models[$provider][$model]);
        }

        foreach ($models as $providerModels) {
            if (isset($providerModels[$model])) {
                return $this->normalize($providerModels[$model]);
            }
        }

        return null;
    }

    /**
     * @param  array<string, float>  $rates
     * @return array{input: float, output: float, cache_write: float, cache_read: float, reasoning: float}
     */
    protected function normalize(array $rates): array
    {
        return [
            'input' => (float) ($rates['input'] ?? 0.0),
            'output' => (float) ($rates['output'] ?? 0.0),
            'cache_write' => (float) ($rates['cache_write'] ?? ($rates['input'] ?? 0.0)),
            'cache_read' => (float) ($rates['cache_read'] ?? ($rates['input'] ?? 0.0)),
            'reasoning' => (float) ($rates['reasoning'] ?? ($rates['output'] ?? 0.0)),
        ];
    }
}
