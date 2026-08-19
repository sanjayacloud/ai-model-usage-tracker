<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Pricing;

use AiModelUsageTracker\AiModelUsageTracker\DataObjects\CostBreakdown;
use AiModelUsageTracker\AiModelUsageTracker\DataObjects\UsageData;
use Illuminate\Support\Facades\Log;

class CostCalculator
{
    private const int PER = 1_000_000;

    /**
     * @var array<string, true>
     */
    private static array $warned = [];

    public function __construct(protected PricingRepository $pricing) {}

    public function calculate(UsageData $usage): CostBreakdown
    {
        $rates = $this->pricing->ratesFor($usage->provider, $usage->model);

        if ($rates === null) {
            $this->warnMissing($usage);

            return new CostBreakdown(
                currency: $this->pricing->currency(),
                pricingFound: false,
            );
        }

        $cacheRead = min($usage->cacheReadInputTokens, $usage->promptTokens);
        $cacheWrite = min($usage->cacheWriteInputTokens, max(0, $usage->promptTokens - $cacheRead));
        $regularInput = max(0, $usage->promptTokens - $cacheRead - $cacheWrite);

        $inputCost = (
            $regularInput * $rates['input']
            + $cacheRead * $rates['cache_read']
            + $cacheWrite * $rates['cache_write']
        ) / self::PER;

        $outputCost = (
            $usage->completionTokens * $rates['output']
            + $usage->reasoningTokens * $rates['reasoning']
        ) / self::PER;

        $units = $this->imageCount($usage);
        $inputCost += $rates['per_image'] * $units;
        $inputCost += $rates['per_second'] * $this->durationSeconds($usage);

        return new CostBreakdown(
            inputCost: round($inputCost, 8),
            outputCost: round($outputCost, 8),
            currency: $this->pricing->currency(),
            pricingFound: true,
        );
    }

    protected function warnMissing(UsageData $usage): void
    {
        $key = ($usage->provider ?? '').'|'.($usage->model ?? '');

        if (isset(self::$warned[$key])) {
            return;
        }

        self::$warned[$key] = true;

        Log::warning('AI usage pricing is missing for model; cost recorded as 0.', [
            'provider' => $usage->provider,
            'model' => $usage->model,
        ]);
    }

    protected function imageCount(UsageData $usage): int
    {
        if ($usage->operation->value !== 'image') {
            $count = (int) ($usage->metadata['images'] ?? $usage->metadata['n'] ?? 0);

            return max(0, $count);
        }

        return max(1, (int) ($usage->metadata['images'] ?? $usage->metadata['n'] ?? 1));
    }

    protected function durationSeconds(UsageData $usage): float
    {
        return (float) ($usage->metadata['seconds'] ?? $usage->metadata['duration'] ?? 0);
    }
}
