<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTracker;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Operation;
use AiModelUsageTracker\AiModelUsageTracker\Events\UsageRecorded;
use AiModelUsageTracker\AiModelUsageTracker\Jobs\RecordUsageJob;
use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

it('records a manual usage entry with computed cost', function () {
    $record = app(AiModelUsageTracker::class)
        ->track()
        ->provider('openai')
        ->model('gpt-4o')
        ->operation(Operation::Chat)
        ->tokens(prompt: 1000, completion: 500)
        ->latency(1200)
        ->record();

    expect($record)->toBeInstanceOf(UsageRecord::class)
        ->and($record->total_tokens)->toBe(1500)
        ->and($record->input_cost)->toBe(0.0025)
        ->and($record->output_cost)->toBe(0.005)
        ->and($record->total_cost)->toBe(0.0075)
        ->and($record->currency)->toBe('USD');
});

it('flags unknown models as missing pricing', function () {
    $record = app(AiModelUsageTracker::class)
        ->track()
        ->provider('acme')
        ->model('mystery-1')
        ->tokens(prompt: 100, completion: 100)
        ->record();

    expect($record->total_cost)->toBe(0.0)
        ->and($record->metadata)->toMatchArray(['pricing_missing' => true]);
});

it('dispatches the UsageRecorded event', function () {
    Event::fake([UsageRecorded::class]);

    app(AiModelUsageTracker::class)->track()->model('gpt-4o')->tokens(prompt: 10)->record();

    Event::assertDispatched(UsageRecorded::class);
});

it('queues recording when configured', function () {
    Queue::fake();
    config()->set('ai-model-usage-tracker.recording.mode', 'queue');

    $result = app(AiModelUsageTracker::class)->track()->model('gpt-4o')->tokens(prompt: 10)->record();

    expect($result)->toBeNull();
    Queue::assertPushed(RecordUsageJob::class);
});

it('fetches usage for a specific invocation', function () {
    app(AiModelUsageTracker::class)->track()->invocation('abc-123')->model('gpt-4o')->tokens(prompt: 10)->record();

    $found = app(AiModelUsageTracker::class)->forInvocation('abc-123');

    expect($found)->not->toBeNull()
        ->and($found->toUsageArray())->toHaveKeys(['tokens', 'cost', 'latency_ms']);
});
