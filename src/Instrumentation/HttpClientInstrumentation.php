<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Instrumentation;

use AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTracker;
use AiModelUsageTracker\AiModelUsageTracker\Contracts\UsageInstrumentation;
use AiModelUsageTracker\AiModelUsageTracker\DataObjects\UsageData;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Driver;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Operation;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;

/**
 * Parses token usage from raw provider HTTP responses (OpenAI / Anthropic
 * compatible JSON) for apps that call the provider APIs directly.
 */
class HttpClientInstrumentation implements UsageInstrumentation
{
    public function __construct(
        protected Config $config,
        protected AiModelUsageTracker $tracker,
    ) {}

    public function shouldRegister(): bool
    {
        return (bool) $this->config->get('ai-model-usage-tracker.instrumentation.http', false);
    }

    public function register(): void
    {
        Http::globalResponseMiddleware(function (ResponseInterface $response): ResponseInterface {
            $this->recordFromResponse($response);

            return $response;
        });
    }

    public function recordFromResponse(ResponseInterface $response): void
    {
        $body = (string) $response->getBody();
        $response->getBody()->rewind();

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($body, true);

        if (! is_array($payload) || ! isset($payload['usage']) || ! is_array($payload['usage'])) {
            return;
        }

        $usage = $payload['usage'];

        $prompt = (int) ($usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0);
        $completion = (int) ($usage['completion_tokens'] ?? $usage['output_tokens'] ?? 0);

        if ($prompt === 0 && $completion === 0) {
            return;
        }

        $this->tracker->record(new UsageData(
            driver: Driver::Http,
            operation: Operation::Chat,
            provider: $this->inferProvider($payload),
            model: isset($payload['model']) ? (string) $payload['model'] : null,
            promptTokens: $prompt,
            completionTokens: $completion,
            cacheReadInputTokens: (int) ($usage['cache_read_input_tokens'] ?? 0),
            cacheWriteInputTokens: (int) ($usage['cache_creation_input_tokens'] ?? 0),
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function inferProvider(array $payload): ?string
    {
        $model = (string) ($payload['model'] ?? '');

        return match (true) {
            str_starts_with($model, 'gpt-'), str_starts_with($model, 'o1'), str_starts_with($model, 'text-embedding') => 'openai',
            str_starts_with($model, 'claude') => 'anthropic',
            str_starts_with($model, 'gemini') => 'gemini',
            default => null,
        };
    }
}
