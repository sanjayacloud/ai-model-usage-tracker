<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Pricing\Catalog;

use Illuminate\Support\Facades\Http;
use Throwable;

class LiteLlmCatalog implements PricingCatalog
{
    public const string URL = 'https://raw.githubusercontent.com/BerriAI/litellm/main/model_prices_and_context_window.json';

    public function __construct(protected int $timeout = 10) {}

    /**
     * @return array<string, array<string, array<string, float>>>
     */
    public function fetch(): array
    {
        try {
            $response = Http::timeout($this->timeout)->acceptJson()->get(self::URL);

            if (! $response->successful()) {
                return [];
            }

            /** @var mixed $payload */
            $payload = $response->json();

            return is_array($payload) ? $this->map($payload) : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<int|string, mixed>  $payload
     * @return array<string, array<string, array<string, float>>>
     */
    protected function map(array $payload): array
    {
        $models = [];

        foreach ($payload as $key => $row) {
            if (! is_string($key) || ! is_array($row) || $key === 'sample_spec') {
                continue;
            }

            $provider = $this->provider($key, $row);
            $model = $this->modelName($key);

            if ($provider === '' || $model === '') {
                continue;
            }

            $models[$provider][$model] = $this->rates($row);
        }

        return $models;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function provider(string $key, array $row): string
    {
        $provider = $row['litellm_provider'] ?? null;

        if (is_string($provider) && $provider !== '') {
            return $this->normalizeProvider($provider);
        }

        if (str_contains($key, '/')) {
            return $this->normalizeProvider(substr($key, 0, (int) strpos($key, '/')));
        }

        return 'openai';
    }

    protected function modelName(string $key): string
    {
        if (str_contains($key, '/')) {
            return substr($key, strrpos($key, '/') + 1) ?: $key;
        }

        return $key;
    }

    protected function normalizeProvider(string $provider): string
    {
        $provider = strtolower($provider);

        return match ($provider) {
            'google', 'vertex_ai', 'vertex_ai-language-models' => 'gemini',
            'azure', 'azure_ai' => 'openai',
            default => $provider,
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, float>
     */
    protected function rates(array $row): array
    {
        $input = $this->perMillion($row['input_cost_per_token'] ?? 0);
        $output = $this->perMillion($row['output_cost_per_token'] ?? 0);

        return [
            'input' => $input,
            'output' => $output,
            'cache_read' => $this->perMillion($row['cache_read_input_token_cost'] ?? $input / 1_000_000),
            'cache_write' => $this->perMillion($row['cache_creation_input_token_cost'] ?? $input / 1_000_000),
            'reasoning' => $this->perMillion($row['output_cost_per_reasoning_token'] ?? $output / 1_000_000),
        ];
    }

    protected function perMillion(mixed $perToken): float
    {
        return round(((float) $perToken) * 1_000_000, 8);
    }
}
