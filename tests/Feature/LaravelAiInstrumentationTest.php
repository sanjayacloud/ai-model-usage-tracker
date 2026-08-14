<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\Enums\Driver;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Operation;
use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\EmbeddingsGenerated;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\EmbeddingsResponse;

function makeAgentPrompted(string $invocationId, AgentResponse $response): AgentPrompted
{
    $event = (new ReflectionClass(AgentPrompted::class))->newInstanceWithoutConstructor();
    $event->invocationId = $invocationId;
    $event->response = $response;

    return $event;
}

it('records chat usage from an AgentPrompted event', function () {
    $response = new AgentResponse(
        'inv-1',
        'Hello there.',
        new Usage(promptTokens: 1000, completionTokens: 500, reasoningTokens: 50),
        new Meta(provider: 'openai', model: 'gpt-4o'),
    );

    event(makeAgentPrompted('inv-1', $response));

    $record = UsageRecord::query()->firstOrFail();

    expect($record->driver)->toBe(Driver::LaravelAi)
        ->and($record->operation)->toBe(Operation::Chat)
        ->and($record->provider)->toBe('openai')
        ->and($record->model)->toBe('gpt-4o')
        ->and($record->invocation_id)->toBe('inv-1')
        ->and($record->prompt_tokens)->toBe(1000)
        ->and($record->completion_tokens)->toBe(500)
        ->and($record->reasoning_tokens)->toBe(50)
        ->and($record->total_cost)->toBeGreaterThan(0.0);
});

it('captures the conversation id from an AgentPrompted event', function () {
    $response = new AgentResponse(
        'inv-conv',
        'Hello there.',
        new Usage(promptTokens: 100, completionTokens: 50),
        new Meta(provider: 'openai', model: 'gpt-4o'),
    );
    $response->withinConversation('conv-123', (object) ['id' => null]);

    event(makeAgentPrompted('inv-conv', $response));

    $record = UsageRecord::query()->firstOrFail();

    expect($record->conversation_id)->toBe('conv-123');
});

it('records embeddings usage from an EmbeddingsGenerated event', function () {
    $event = (new ReflectionClass(EmbeddingsGenerated::class))->newInstanceWithoutConstructor();
    $event->invocationId = 'inv-embed';
    $event->model = 'text-embedding-3-small';
    $event->response = new EmbeddingsResponse(
        embeddings: [[0.1, 0.2]],
        tokens: 2000,
        meta: new Meta(provider: 'openai', model: 'text-embedding-3-small'),
    );

    event($event);

    $record = UsageRecord::query()->firstOrFail();

    expect($record->operation)->toBe(Operation::Embeddings)
        ->and($record->provider)->toBe('openai')
        ->and($record->prompt_tokens)->toBe(2000);
});
