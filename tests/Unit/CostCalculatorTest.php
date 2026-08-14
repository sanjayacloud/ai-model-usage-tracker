<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\DataObjects\UsageData;
use AiModelUsageTracker\AiModelUsageTracker\Pricing\CostCalculator;

it('calculates cost from per-million rates', function () {
    $cost = app(CostCalculator::class)->calculate(new UsageData(
        provider: 'openai',
        model: 'gpt-4o',
        promptTokens: 1_000_000,
        completionTokens: 1_000_000,
    ));

    expect($cost->pricingFound)->toBeTrue()
        ->and($cost->inputCost)->toBe(2.5)
        ->and($cost->outputCost)->toBe(10.0)
        ->and($cost->totalCost())->toBe(12.5);
});

it('prices cached read tokens at the reduced rate', function () {
    $cost = app(CostCalculator::class)->calculate(new UsageData(
        provider: 'anthropic',
        model: 'claude-3-5-sonnet-latest',
        promptTokens: 1_000_000,
        cacheReadInputTokens: 1_000_000,
    ));

    // All prompt tokens are cache reads at 0.30 / 1M.
    expect($cost->inputCost)->toBe(0.3);
});

it('reports missing pricing for unknown models', function () {
    $cost = app(CostCalculator::class)->calculate(new UsageData(
        provider: 'acme',
        model: 'unknown',
        promptTokens: 1000,
    ));

    expect($cost->pricingFound)->toBeFalse()
        ->and($cost->totalCost())->toBe(0.0);
});

it('finds pricing by model when provider is omitted', function () {
    $cost = app(CostCalculator::class)->calculate(new UsageData(
        model: 'gpt-4o-mini',
        promptTokens: 1_000_000,
    ));

    expect($cost->pricingFound)->toBeTrue()
        ->and($cost->inputCost)->toBe(0.15);
});
