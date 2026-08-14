<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTracker;
use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;
use Carbon\CarbonImmutable;

it('prunes records older than the retention window', function () {
    app(AiModelUsageTracker::class)->track()->model('gpt-4o')->tokens(prompt: 10)->record();
    $old = UsageRecord::query()->firstOrFail();
    $old->forceFill(['created_at' => CarbonImmutable::now()->subDays(120)])->saveQuietly();

    app(AiModelUsageTracker::class)->track()->model('gpt-4o')->tokens(prompt: 10)->record();

    $this->artisan('ai-usage:prune', ['--days' => 30])
        ->assertSuccessful();

    expect(UsageRecord::query()->count())->toBe(1);
});

it('does nothing without a retention window', function () {
    app(AiModelUsageTracker::class)->track()->model('gpt-4o')->tokens(prompt: 10)->record();

    $this->artisan('ai-usage:prune')->assertSuccessful();

    expect(UsageRecord::query()->count())->toBe(1);
});
