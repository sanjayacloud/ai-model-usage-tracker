<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\DataObjects\UsageData;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Operation;
use AiModelUsageTracker\AiModelUsageTracker\Pricing\CostCalculator;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

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
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldIgnoreMissing();
    $logger->shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => str_contains($message, 'pricing is missing')
            && $context['provider'] === 'acme'
            && $context['model'] === 'unknown');
    Log::swap($logger);

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

it('matches dated model ids to the longest catalog prefix', function () {
    $cost = app(CostCalculator::class)->calculate(new UsageData(
        provider: 'openai',
        model: 'gpt-4o-2024-11-20',
        promptTokens: 1_000_000,
    ));

    expect($cost->pricingFound)->toBeTrue()
        ->and($cost->inputCost)->toBe(2.5);
});

it('prefers gpt-4o-mini over gpt-4o for mini variants', function () {
    $cost = app(CostCalculator::class)->calculate(new UsageData(
        model: 'gpt-4o-mini-2024-07-18',
        promptTokens: 1_000_000,
    ));

    expect($cost->inputCost)->toBe(0.15);
});

it('does not treat gpt-4o as a prefix of gpt-4', function () {
    $cost = app(CostCalculator::class)->calculate(new UsageData(
        model: 'gpt-4',
        promptTokens: 1000,
    ));

    expect($cost->pricingFound)->toBeFalse();
});

it('strips provider prefixes from model names', function () {
    $cost = app(CostCalculator::class)->calculate(new UsageData(
        provider: 'gemini',
        model: 'google/gemini-3.1-flash-lite',
        promptTokens: 1_000_000,
    ));

    expect($cost->pricingFound)->toBeTrue()
        ->and($cost->inputCost)->toBe(0.25);
});

it('adds per-image rates for image operations', function () {
    config()->set('ai-model-usage-tracker.pricing.models.openai.gpt-image-1', [
        'input' => 0,
        'output' => 0,
        'per_image' => 0.04,
    ]);

    $cost = app(CostCalculator::class)->calculate(new UsageData(
        operation: Operation::Image,
        provider: 'openai',
        model: 'gpt-image-1',
        metadata: ['n' => 2],
    ));

    expect($cost->pricingFound)->toBeTrue()
        ->and($cost->inputCost)->toBe(0.08);
});

it('adds per-second rates for audio operations', function () {
    config()->set('ai-model-usage-tracker.pricing.models.openai.tts-1', [
        'input' => 0,
        'output' => 0,
        'per_second' => 0.015,
    ]);

    $cost = app(CostCalculator::class)->calculate(new UsageData(
        operation: Operation::Audio,
        provider: 'openai',
        model: 'tts-1',
        metadata: ['seconds' => 10],
    ));

    expect($cost->pricingFound)->toBeTrue()
        ->and($cost->inputCost)->toBe(0.15);
});
