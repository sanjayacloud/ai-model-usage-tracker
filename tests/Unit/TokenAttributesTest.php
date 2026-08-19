<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\Support\TokenAttributes;

it('prefers promptTokens then falls back to inputTokens', function () {
    $legacy = new class
    {
        public int $inputTokens = 42;
    };

    $modern = new class
    {
        public int $promptTokens = 10;

        public int $inputTokens = 99;
    };

    expect(TokenAttributes::int($legacy, 'promptTokens', 'inputTokens'))->toBe(42)
        ->and(TokenAttributes::int($modern, 'promptTokens', 'inputTokens'))->toBe(10)
        ->and(TokenAttributes::int($legacy, 'completionTokens', 'outputTokens'))->toBe(0);
});
