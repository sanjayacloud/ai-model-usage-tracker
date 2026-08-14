<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\Enums\Driver;
use AiModelUsageTracker\AiModelUsageTracker\Instrumentation\HttpClientInstrumentation;
use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;
use GuzzleHttp\Psr7\Response;

it('parses OpenAI-style usage from a raw HTTP response', function () {
    $response = new Response(200, [], (string) json_encode([
        'model' => 'gpt-4o',
        'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 200],
    ]));

    app(HttpClientInstrumentation::class)->recordFromResponse($response);

    $record = UsageRecord::query()->firstOrFail();

    expect($record->driver)->toBe(Driver::Http)
        ->and($record->provider)->toBe('openai')
        ->and($record->model)->toBe('gpt-4o')
        ->and($record->prompt_tokens)->toBe(100)
        ->and($record->completion_tokens)->toBe(200);
});

it('parses Anthropic-style token fields', function () {
    $response = new Response(200, [], (string) json_encode([
        'model' => 'claude-3-5-sonnet-latest',
        'usage' => ['input_tokens' => 50, 'output_tokens' => 75],
    ]));

    app(HttpClientInstrumentation::class)->recordFromResponse($response);

    $record = UsageRecord::query()->firstOrFail();

    expect($record->provider)->toBe('anthropic')
        ->and($record->prompt_tokens)->toBe(50)
        ->and($record->completion_tokens)->toBe(75);
});

it('ignores responses without usage data', function () {
    $response = new Response(200, [], (string) json_encode(['choices' => []]));

    app(HttpClientInstrumentation::class)->recordFromResponse($response);

    expect(UsageRecord::query()->count())->toBe(0);
});
