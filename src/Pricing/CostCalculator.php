<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Pricing;

use AiModelUsageTracker\AiModelUsageTracker\DataObjects\CostBreakdown;
use AiModelUsageTracker\AiModelUsageTracker\DataObjects\UsageData;

class CostCalculator
{
    private const int PER = 1_000_000;

    public function __construct(protected PricingRepository $pricing) {}

    public function calculate(UsageData $usage): CostBreakdown
    {
        $rates = $this->pricing->ratesFor($usage->provider, $usage->model);

        if ($rates === null) {
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

        return new CostBreakdown(
            inputCost: round($inputCost, 8),
            outputCost: round($outputCost, 8),
            currency: $this->pricing->currency(),
            pricingFound: true,
        );
    }
}
