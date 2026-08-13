<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTracker;

it('resolves the singleton', function () {
    expect(app(AiModelUsageTracker::class))->toBeInstanceOf(AiModelUsageTracker::class);
});

it('returns the same instance from the container', function () {
    expect(app(AiModelUsageTracker::class))->toBe(app(AiModelUsageTracker::class));
});

it('merges the package config', function () {
    expect(config('ai-model-usage-tracker.placeholder'))->toBe('default');
});

it('loads the package translations', function () {
    expect(trans('ai-model-usage-tracker::messages.placeholder'))->toBe('AiModelUsageTracker placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('ai-model-usage-tracker::placeholder'))->toBeTrue();
});

it('registers the artisan command', function () {
    $this->artisan('ai-model-usage-tracker:placeholder')
        ->expectsOutputToContain('AiModelUsageTracker placeholder command executed.')
        ->assertSuccessful();
});
