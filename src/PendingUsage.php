<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker;

use AiModelUsageTracker\AiModelUsageTracker\DataObjects\UsageData;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Driver;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Operation;
use AiModelUsageTracker\AiModelUsageTracker\Enums\UsageStatus;
use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Fluent builder for recording usage manually.
 */
class PendingUsage
{
    protected Driver $driver = Driver::Manual;

    protected Operation $operation = Operation::Chat;

    protected ?string $provider = null;

    protected ?string $model = null;

    protected ?string $invocationId = null;

    protected ?string $conversationId = null;

    protected ?string $featureKey = null;

    protected int $promptTokens = 0;

    protected int $completionTokens = 0;

    protected int $cacheWriteInputTokens = 0;

    protected int $cacheReadInputTokens = 0;

    protected int $reasoningTokens = 0;

    protected ?int $latencyMs = null;

    protected UsageStatus $status = UsageStatus::Success;

    protected ?string $error = null;

    protected bool $streamed = false;

    protected ?string $trackableType = null;

    protected int|string|null $trackableId = null;

    /** @var array<string, mixed> */
    protected array $metadata = [];

    protected ?DateTimeInterface $startedAt = null;

    protected ?DateTimeInterface $endedAt = null;

    public function __construct(protected AiModelUsageTracker $tracker) {}

    public function driver(Driver $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    public function operation(Operation $operation): self
    {
        $this->operation = $operation;

        return $this;
    }

    public function provider(string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function invocation(string $invocationId): self
    {
        $this->invocationId = $invocationId;

        return $this;
    }

    public function conversation(string $conversationId): self
    {
        $this->conversationId = $conversationId;

        return $this;
    }

    public function feature(string $featureKey): self
    {
        $this->featureKey = $featureKey;

        return $this;
    }

    public function tokens(int $prompt = 0, int $completion = 0, int $cacheWrite = 0, int $cacheRead = 0, int $reasoning = 0): self
    {
        $this->promptTokens = $prompt;
        $this->completionTokens = $completion;
        $this->cacheWriteInputTokens = $cacheWrite;
        $this->cacheReadInputTokens = $cacheRead;
        $this->reasoningTokens = $reasoning;

        return $this;
    }

    public function latency(int $milliseconds): self
    {
        $this->latencyMs = $milliseconds;

        return $this;
    }

    public function status(UsageStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function failed(?string $error = null): self
    {
        $this->status = UsageStatus::Failed;
        $this->error = $error;

        return $this;
    }

    public function streamed(bool $streamed = true): self
    {
        $this->streamed = $streamed;

        return $this;
    }

    public function for(Model $trackable): self
    {
        $this->trackableType = $trackable->getMorphClass();
        $this->trackableId = $trackable->getKey();

        return $this;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function meta(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata, $metadata);

        return $this;
    }

    public function startedAt(DateTimeInterface $at): self
    {
        $this->startedAt = $at;

        return $this;
    }

    public function endedAt(DateTimeInterface $at): self
    {
        $this->endedAt = $at;

        return $this;
    }

    public function toData(): UsageData
    {
        return new UsageData(
            driver: $this->driver,
            operation: $this->operation,
            provider: $this->provider,
            model: $this->model,
            invocationId: $this->invocationId,
            conversationId: $this->conversationId,
            featureKey: $this->featureKey,
            promptTokens: $this->promptTokens,
            completionTokens: $this->completionTokens,
            cacheWriteInputTokens: $this->cacheWriteInputTokens,
            cacheReadInputTokens: $this->cacheReadInputTokens,
            reasoningTokens: $this->reasoningTokens,
            latencyMs: $this->latencyMs,
            status: $this->status,
            error: $this->error,
            streamed: $this->streamed,
            trackableType: $this->trackableType,
            trackableId: $this->trackableId,
            metadata: $this->metadata,
            startedAt: $this->startedAt,
            endedAt: $this->endedAt,
        );
    }

    public function record(): ?UsageRecord
    {
        return $this->tracker->record($this->toData());
    }
}
