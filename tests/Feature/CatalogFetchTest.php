<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\DataObjects\UsageData;
use AiModelUsageTracker\AiModelUsageTracker\Pricing\Catalog\LiteLlmCatalog;
use AiModelUsageTracker\AiModelUsageTracker\Pricing\Catalog\OpenRouterCatalog;
use AiModelUsageTracker\AiModelUsageTracker\Pricing\CatalogManager;
use AiModelUsageTracker\AiModelUsageTracker\Pricing\CatalogStore;
use AiModelUsageTracker\AiModelUsageTracker\Pricing\CostCalculator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Http::preventStrayRequests();
});

it('fetches LiteLLM rates and uses them when config has no match', function () {
    Http::fake([
        LiteLlmCatalog::URL => Http::response([
            'mystery-pro' => [
                'litellm_provider' => 'acme',
                'input_cost_per_token' => 0.000001,
                'output_cost_per_token' => 0.000002,
            ],
        ]),
        '*' => Http::response(['data' => []], 200),
    ]);

    app(CatalogManager::class)->refresh(force: true);

    $cost = app(CostCalculator::class)->calculate(new UsageData(
        provider: 'acme',
        model: 'mystery-pro',
        promptTokens: 1_000_000,
        completionTokens: 1_000_000,
    ));

    expect($cost->pricingFound)->toBeTrue()
        ->and($cost->inputCost)->toBe(1.0)
        ->and($cost->outputCost)->toBe(2.0);
});

it('lets published config rates win over the fetched catalog', function () {
    Http::fake([
        LiteLlmCatalog::URL => Http::response([
            'gpt-4o' => [
                'litellm_provider' => 'openai',
                'input_cost_per_token' => 0.000099,
                'output_cost_per_token' => 0.000099,
            ],
        ]),
        '*' => Http::response(['data' => []], 200),
    ]);

    app(CatalogManager::class)->refresh(force: true);

    $cost = app(CostCalculator::class)->calculate(new UsageData(
        provider: 'openai',
        model: 'gpt-4o',
        promptTokens: 1_000_000,
    ));

    expect($cost->inputCost)->toBe(2.5);
});

it('skips the network when a fresh cache exists', function () {
    Http::fake();
    app(CatalogStore::class)->put(['acme' => ['cached' => ['input' => 1, 'output' => 1]]], 24);

    $result = app(CatalogManager::class)->refresh(force: false);

    expect($result['skipped'])->toBeTrue()
        ->and($result['source'])->toBe('cache');
    Http::assertNothingSent();
});

it('fills LiteLLM gaps from OpenRouter', function () {
    Http::fake([
        LiteLlmCatalog::URL => Http::response([]),
        'https://openrouter.ai/api/v1/models' => Http::response([
            'data' => [[
                'id' => 'acme/or-model',
                'pricing' => ['prompt' => '0.000003', 'completion' => '0.000006'],
            ]],
        ]),
    ]);

    $result = app(CatalogManager::class)->refresh(force: true);

    expect($result['skipped'])->toBeFalse()
        ->and($result['source'])->toBe('openrouter')
        ->and($result['models'])->toBe(1);

    $cost = app(CostCalculator::class)->calculate(new UsageData(
        provider: 'acme',
        model: 'or-model',
        promptTokens: 1_000_000,
    ));

    expect($cost->pricingFound)->toBeTrue()
        ->and($cost->inputCost)->toBe(3.0);
});

it('refreshes the catalog from the artisan command', function () {
    Http::fake([
        LiteLlmCatalog::URL => Http::response([
            'cmd-model' => [
                'litellm_provider' => 'acme',
                'input_cost_per_token' => 0.000001,
                'output_cost_per_token' => 0,
            ],
        ]),
        '*' => Http::response(['data' => []], 200),
    ]);

    $this->artisan('ai-usage:fetch-pricing', ['--force' => true])->assertSuccessful();

    expect(app(CatalogStore::class)->models()['acme']['cmd-model']['input'])->toBe(1.0);
});

it('falls back to OpenRouter when LiteLLM times out', function () {
    Http::fake([
        LiteLlmCatalog::URL => Http::response('timeout', 504),
        OpenRouterCatalog::URL => Http::response([
            'data' => [[
                'id' => 'acme/fallback-model',
                'pricing' => ['prompt' => '0.000004', 'completion' => '0'],
            ]],
        ]),
    ]);

    $result = app(CatalogManager::class)->refresh(force: true);

    expect($result['skipped'])->toBeFalse()
        ->and($result['source'])->toBe('openrouter');

    $cost = app(CostCalculator::class)->calculate(new UsageData(
        provider: 'acme',
        model: 'fallback-model',
        promptTokens: 1_000_000,
    ));

    expect($cost->inputCost)->toBe(4.0);
});

it('reads the catalog from disk when the cache is empty', function () {
    Cache::forget(CatalogStore::CACHE_KEY);
    Storage::disk('local')->put(CatalogStore::DISK_PATH, json_encode([
        'acme' => ['disk-model' => ['input' => 7.0, 'output' => 0.0]],
    ]));

    $cost = app(CostCalculator::class)->calculate(new UsageData(
        provider: 'acme',
        model: 'disk-model',
        promptTokens: 1_000_000,
    ));

    expect($cost->pricingFound)->toBeTrue()
        ->and($cost->inputCost)->toBe(7.0);
});
