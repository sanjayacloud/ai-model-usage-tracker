<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\DataObjects;

use AiModelUsageTracker\AiModelUsageTracker\Enums\Driver;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Operation;
use AiModelUsageTracker\AiModelUsageTracker\Enums\UsageStatus;
use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Immutable description of a single AI request's usage, prior to persistence.
 */
final class UsageData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public Driver $driver = Driver::Manual,
        public Operation $operation = Operation::Chat,
        public ?string $provider = null,
        public ?string $model = null,
        public ?string $invocationId = null,
        public ?string $conversationId = null,
        public ?string $featureKey = null,
        public int $promptTokens = 0,
        public int $completionTokens = 0,
        public int $cacheWriteInputTokens = 0,
        public int $cacheReadInputTokens = 0,
        public int $reasoningTokens = 0,
        public ?int $latencyMs = null,
        public UsageStatus $status = UsageStatus::Success,
        public ?string $error = null,
        public bool $streamed = false,
        public ?string $trackableType = null,
        public int|string|null $trackableId = null,
        public array $metadata = [],
        public ?DateTimeInterface $startedAt = null,
        public ?DateTimeInterface $endedAt = null,
    ) {}

    /**
     * Total billable tokens. Cache and reasoning tokens are subsets of the
     * prompt/completion counts and are intentionally not summed again.
     */
    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'driver' => $this->driver->value,
            'operation' => $this->operation->value,
            'provider' => $this->provider,
            'model' => $this->model,
            'invocation_id' => $this->invocationId,
            'conversation_id' => $this->conversationId,
            'feature_key' => $this->featureKey,
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'cache_write_input_tokens' => $this->cacheWriteInputTokens,
            'cache_read_input_tokens' => $this->cacheReadInputTokens,
            'reasoning_tokens' => $this->reasoningTokens,
            'latency_ms' => $this->latencyMs,
            'status' => $this->status->value,
            'error' => $this->error,
            'streamed' => $this->streamed,
            'trackable_type' => $this->trackableType,
            'trackable_id' => $this->trackableId,
            'metadata' => $this->metadata,
            'started_at' => $this->startedAt?->format(DateTimeInterface::ATOM),
            'ended_at' => $this->endedAt?->format(DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            driver: Driver::from($data['driver'] ?? Driver::Manual->value),
            operation: Operation::from($data['operation'] ?? Operation::Chat->value),
            provider: $data['provider'] ?? null,
            model: $data['model'] ?? null,
            invocationId: $data['invocation_id'] ?? null,
            conversationId: $data['conversation_id'] ?? null,
            featureKey: $data['feature_key'] ?? null,
            promptTokens: (int) ($data['prompt_tokens'] ?? 0),
            completionTokens: (int) ($data['completion_tokens'] ?? 0),
            cacheWriteInputTokens: (int) ($data['cache_write_input_tokens'] ?? 0),
            cacheReadInputTokens: (int) ($data['cache_read_input_tokens'] ?? 0),
            reasoningTokens: (int) ($data['reasoning_tokens'] ?? 0),
            latencyMs: isset($data['latency_ms']) ? (int) $data['latency_ms'] : null,
            status: UsageStatus::from($data['status'] ?? UsageStatus::Success->value),
            error: $data['error'] ?? null,
            streamed: (bool) ($data['streamed'] ?? false),
            trackableType: $data['trackable_type'] ?? null,
            trackableId: $data['trackable_id'] ?? null,
            metadata: $data['metadata'] ?? [],
            startedAt: isset($data['started_at']) ? CarbonImmutable::parse($data['started_at']) : null,
            endedAt: isset($data['ended_at']) ? CarbonImmutable::parse($data['ended_at']) : null,
        );
    }
}
