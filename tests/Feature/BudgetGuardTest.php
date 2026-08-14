<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTracker;
use AiModelUsageTracker\AiModelUsageTracker\Events\BudgetThresholdReached;
use Illuminate\Support\Facades\Event;

it('dispatches a budget event once a threshold is crossed', function () {
    config()->set('ai-model-usage-tracker.budgets', [
        'monthly' => ['period' => 'month', 'limit' => 1.0, 'thresholds' => [0.8, 1.0]],
    ]);

    Event::fake([BudgetThresholdReached::class]);

    // gpt-4o at 1M prompt + 1M completion tokens = $12.50, well past the $1 cap.
    app(AiModelUsageTracker::class)
        ->track()
        ->provider('openai')
        ->model('gpt-4o')
        ->tokens(prompt: 1_000_000, completion: 1_000_000)
        ->record();

    Event::assertDispatched(BudgetThresholdReached::class, fn (BudgetThresholdReached $e) => $e->name === 'monthly' && $e->threshold === 1.0);
});

it('does not dispatch a budget event below the threshold', function () {
    config()->set('ai-model-usage-tracker.budgets', [
        'monthly' => ['period' => 'month', 'limit' => 1000.0, 'thresholds' => [1.0]],
    ]);

    Event::fake([BudgetThresholdReached::class]);

    app(AiModelUsageTracker::class)->track()->provider('openai')->model('gpt-4o')->tokens(prompt: 10)->record();

    Event::assertNotDispatched(BudgetThresholdReached::class);
});
