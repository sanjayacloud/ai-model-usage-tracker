<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Pricing\Catalog;

use Illuminate\Support\Facades\Http;
use Throwable;

class OpenRouterCatalog implements PricingCatalog
{
    public const string URL = 'https://openrouter.ai/api/v1/models';

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

            $rows = $response->json('data');

            return is_array($rows) ? $this->map($rows) : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  list<mixed>  $rows
     * @return array<string, array<string, array<string, float>>>
     */
    protected function map(array $rows): array
    {
        $models = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! is_string($row['id'] ?? null)) {
                continue;
            }

            $id = $row['id'];
            $pricing = $row['pricing'] ?? [];

            if (! is_array($pricing)) {
                continue;
            }

            $slash = strpos($id, '/');
            $provider = $slash === false ? 'openai' : substr($id, 0, $slash);
            $model = $slash === false ? $id : substr($id, $slash + 1);

            if ($provider === '' || $model === '') {
                continue;
            }

            $provider = $this->normalizeProvider($provider);
            $input = $this->perMillion($pricing['prompt'] ?? 0);
            $output = $this->perMillion($pricing['completion'] ?? 0);

            $models[$provider][$model] = [
                'input' => $input,
                'output' => $output,
                'cache_read' => $this->perMillion($pricing['input_cache_read'] ?? $input / 1_000_000),
                'cache_write' => $this->perMillion($pricing['input_cache_write'] ?? $input / 1_000_000),
                'reasoning' => $output,
            ];
        }

        return $models;
    }

    protected function normalizeProvider(string $provider): string
    {
        $provider = strtolower($provider);

        return match ($provider) {
            'google', 'google-ai-studio', 'google-vertex' => 'gemini',
            default => $provider,
        };
    }

    protected function perMillion(mixed $perToken): float
    {
        return round(((float) $perToken) * 1_000_000, 8);
    }
}
