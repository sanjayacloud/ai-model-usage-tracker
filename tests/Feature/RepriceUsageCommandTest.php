<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTracker;
use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;

it('reprices rows that were recorded without a rate', function () {
    $record = app(AiModelUsageTracker::class)
        ->track()
        ->provider('openai')
        ->model('unknown-dated')
        ->tokens(prompt: 1_000_000, completion: 0)
        ->record();

    expect($record->metadata)->toMatchArray(['pricing_missing' => true])
        ->and($record->total_cost)->toBe(0.0);

    config()->set('ai-model-usage-tracker.pricing.models.openai.unknown-dated', [
        'input' => 1.0,
        'output' => 0,
    ]);

    $this->artisan('ai-usage:reprice')->assertSuccessful();

    $record->refresh();

    expect($record->input_cost)->toBe(1.0)
        ->and($record->total_cost)->toBe(1.0)
        ->and($record->metadata ?? [])->not->toHaveKey('pricing_missing');
});

it('limits reprice to a conversation id', function () {
    app(AiModelUsageTracker::class)
        ->track()
        ->conversation('conv-a')
        ->provider('openai')
        ->model('reprice-a')
        ->tokens(prompt: 1_000_000)
        ->record();

    app(AiModelUsageTracker::class)
        ->track()
        ->conversation('conv-b')
        ->provider('openai')
        ->model('reprice-b')
        ->tokens(prompt: 1_000_000)
        ->record();

    config()->set('ai-model-usage-tracker.pricing.models.openai.reprice-a', ['input' => 2.0, 'output' => 0]);
    config()->set('ai-model-usage-tracker.pricing.models.openai.reprice-b', ['input' => 3.0, 'output' => 0]);

    $this->artisan('ai-usage:reprice', ['--conversation' => 'conv-a'])->assertSuccessful();

    expect(UsageRecord::query()->where('conversation_id', 'conv-a')->value('total_cost'))->toBe(2.0)
        ->and(UsageRecord::query()->where('conversation_id', 'conv-b')->value('total_cost'))->toBe(0.0);
});

it('does not write during a dry run', function () {
    app(AiModelUsageTracker::class)
        ->track()
        ->provider('openai')
        ->model('still-unknown')
        ->tokens(prompt: 100)
        ->record();

    $this->artisan('ai-usage:reprice', ['--dry-run' => true])->assertSuccessful();

    expect(UsageRecord::query()->first()->total_cost)->toBe(0.0);
});
