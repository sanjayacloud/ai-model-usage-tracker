<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Pricing;

use Illuminate\Contracts\Config\Repository as Config;

class PricingRepository
{
    public function __construct(
        protected Config $config,
        protected CatalogStore $catalog,
    ) {}

    public function currency(): string
    {
        return (string) $this->config->get('ai-model-usage-tracker.pricing.currency', 'USD');
    }

    /**
     * Resolve per-1M-token rates for a provider/model pair.
     *
     * Order: published/bundled config, then the fetched catalog. Exact matches
     * win, then the longest prefix match (gpt-4o-2024-11-20 → gpt-4o).
     *
     * @return array{input: float, output: float, cache_write: float, cache_read: float, reasoning: float, per_image: float, per_second: float}|null
     */
    public function ratesFor(?string $provider, ?string $model): ?array
    {
        if ($model === null || $model === '') {
            return null;
        }

        $normalized = $this->normalizeModelName($model);

        foreach ($this->sources() as $models) {
            $hit = $this->lookup($models, $provider, $normalized);

            if ($hit !== null) {
                return $hit;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, array<string, array<string, float>>>>
     */
    protected function sources(): array
    {
        $sources = [$this->configModels()];
        $catalog = $this->catalog->models();

        if ($catalog !== []) {
            $sources[] = $catalog;
        }

        return $sources;
    }

    /**
     * @return array<string, array<string, array<string, float>>>
     */
    protected function configModels(): array
    {
        /** @var array<string, array<string, array<string, float>>> $models */
        $models = $this->config->get('ai-model-usage-tracker.pricing.models', []);

        return $models;
    }

    /**
     * @param  array<string, array<string, array<string, float>>>  $models
     * @return array{input: float, output: float, cache_write: float, cache_read: float, reasoning: float, per_image: float, per_second: float}|null
     */
    protected function lookup(array $models, ?string $provider, string $model): ?array
    {
        if ($provider !== null && isset($models[$provider][$model])) {
            return $this->normalize($models[$provider][$model]);
        }

        foreach ($models as $providerModels) {
            if (isset($providerModels[$model])) {
                return $this->normalize($providerModels[$model]);
            }
        }

        $pool = [];

        if ($provider !== null && isset($models[$provider])) {
            $pool[] = $models[$provider];
        }

        foreach ($models as $providerModels) {
            $pool[] = $providerModels;
        }

        $bestRates = null;
        $bestLength = 0;

        foreach ($pool as $providerModels) {
            foreach ($providerModels as $key => $rates) {
                $candidate = strtolower((string) $key);
                $length = strlen($candidate);

                if ($length <= $bestLength || ! $this->isPrefixOf($candidate, strtolower($model))) {
                    continue;
                }

                $bestRates = $rates;
                $bestLength = $length;
            }
        }

        return $bestRates !== null ? $this->normalize($bestRates) : null;
    }

    protected function isPrefixOf(string $key, string $model): bool
    {
        if ($key === $model) {
            return true;
        }

        if (! str_starts_with($model, $key)) {
            return false;
        }

        $next = $model[strlen($key)] ?? '';

        return in_array($next, ['-', '.', '/', ':'], true);
    }

    protected function normalizeModelName(string $model): string
    {
        $trimmed = trim($model);

        if (str_contains($trimmed, '/')) {
            return substr($trimmed, strrpos($trimmed, '/') + 1) ?: $trimmed;
        }

        return $trimmed;
    }

    /**
     * @param  array<string, float>  $rates
     * @return array{input: float, output: float, cache_write: float, cache_read: float, reasoning: float, per_image: float, per_second: float}
     */
    protected function normalize(array $rates): array
    {
        return [
            'input' => (float) ($rates['input'] ?? 0.0),
            'output' => (float) ($rates['output'] ?? 0.0),
            'cache_write' => (float) ($rates['cache_write'] ?? ($rates['input'] ?? 0.0)),
            'cache_read' => (float) ($rates['cache_read'] ?? ($rates['input'] ?? 0.0)),
            'reasoning' => (float) ($rates['reasoning'] ?? ($rates['output'] ?? 0.0)),
            'per_image' => (float) ($rates['per_image'] ?? 0.0),
            'per_second' => (float) ($rates['per_second'] ?? 0.0),
        ];
    }
}
