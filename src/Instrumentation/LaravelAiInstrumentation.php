<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Instrumentation;

use AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTracker;
use AiModelUsageTracker\AiModelUsageTracker\Contracts\UsageInstrumentation;
use AiModelUsageTracker\AiModelUsageTracker\DataObjects\UsageData;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Driver;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Operation;
use AiModelUsageTracker\AiModelUsageTracker\Enums\UsageStatus;
use AiModelUsageTracker\AiModelUsageTracker\Support\TokenAttributes;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\AgentStreamed;
use Laravel\Ai\Events\AudioGenerated;
use Laravel\Ai\Events\EmbeddingsGenerated;
use Laravel\Ai\Events\GeneratingAudio;
use Laravel\Ai\Events\GeneratingEmbeddings;
use Laravel\Ai\Events\GeneratingImage;
use Laravel\Ai\Events\GeneratingTranscription;
use Laravel\Ai\Events\ImageGenerated;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\ProviderFailedOver;
use Laravel\Ai\Events\StreamingAgent;
use Laravel\Ai\Events\TranscriptionGenerated;

/**
 * Captures usage emitted by the first-party laravel/ai SDK via its events.
 */
class LaravelAiInstrumentation implements UsageInstrumentation
{
    /**
     * Request start times keyed by invocation id, used to derive latency.
     *
     * @var array<string, float>
     */
    protected array $startedAt = [];

    public function __construct(
        protected Config $config,
        protected Dispatcher $events,
        protected AiModelUsageTracker $tracker,
    ) {}

    public function shouldRegister(): bool
    {
        return (bool) $this->config->get('ai-model-usage-tracker.instrumentation.laravel-ai', true)
            && class_exists(AgentPrompted::class);
    }

    public function register(): void
    {
        foreach ([
            PromptingAgent::class,
            StreamingAgent::class,
            GeneratingEmbeddings::class,
            GeneratingImage::class,
            GeneratingAudio::class,
            GeneratingTranscription::class,
        ] as $event) {
            $this->events->listen($event, fn (object $e) => $this->markStart($e));
        }

        $this->events->listen(AgentPrompted::class, fn (object $e) => $this->handleAgent($e, streamed: false));
        $this->events->listen(AgentStreamed::class, fn (object $e) => $this->handleAgent($e, streamed: true));
        $this->events->listen(EmbeddingsGenerated::class, fn (object $e) => $this->handleEmbeddings($e));
        $this->events->listen(ImageGenerated::class, fn (object $e) => $this->handleImage($e));
        $this->events->listen(AudioGenerated::class, fn (object $e) => $this->handleMedia($e, Operation::Audio));
        $this->events->listen(TranscriptionGenerated::class, fn (object $e) => $this->handleMedia($e, Operation::Transcription));
        $this->events->listen(ProviderFailedOver::class, fn (object $e) => $this->handleFailover($e));
    }

    protected function markStart(object $event): void
    {
        if (isset($event->invocationId)) {
            $this->startedAt[$event->invocationId] = microtime(true);
        }
    }

    protected function handleAgent(object $event, bool $streamed): void
    {
        $response = $event->response;
        $usage = $response->usage;
        $meta = $response->meta;

        $this->tracker->record(new UsageData(
            driver: Driver::LaravelAi,
            operation: Operation::Chat,
            provider: $meta->provider ?? null,
            model: $meta->model ?? null,
            invocationId: $event->invocationId,
            conversationId: $response->conversationId ?? null,
            promptTokens: TokenAttributes::int($usage, 'promptTokens', 'inputTokens'),
            completionTokens: TokenAttributes::int($usage, 'completionTokens', 'outputTokens'),
            cacheWriteInputTokens: TokenAttributes::int($usage, 'cacheWriteInputTokens'),
            cacheReadInputTokens: TokenAttributes::int($usage, 'cacheReadInputTokens'),
            reasoningTokens: TokenAttributes::int($usage, 'reasoningTokens'),
            latencyMs: $this->latencyFor($event->invocationId),
            streamed: $streamed,
            startedAt: $this->startTimeFor($event->invocationId),
            endedAt: CarbonImmutable::now(),
        ));
    }

    protected function handleEmbeddings(object $event): void
    {
        $response = $event->response;

        $this->tracker->record(new UsageData(
            driver: Driver::LaravelAi,
            operation: Operation::Embeddings,
            provider: $this->providerName($event),
            model: $event->model,
            invocationId: $event->invocationId,
            promptTokens: TokenAttributes::int($response, 'tokens', 'promptTokens', 'inputTokens'),
            latencyMs: $this->latencyFor($event->invocationId),
            startedAt: $this->startTimeFor($event->invocationId),
            endedAt: CarbonImmutable::now(),
        ));
    }

    protected function handleImage(object $event): void
    {
        $usage = $event->response->usage;

        $this->tracker->record(new UsageData(
            driver: Driver::LaravelAi,
            operation: Operation::Image,
            provider: $this->providerName($event),
            model: $event->model,
            invocationId: $event->invocationId,
            promptTokens: TokenAttributes::int($usage, 'promptTokens', 'inputTokens'),
            completionTokens: TokenAttributes::int($usage, 'completionTokens', 'outputTokens'),
            latencyMs: $this->latencyFor($event->invocationId),
            startedAt: $this->startTimeFor($event->invocationId),
            endedAt: CarbonImmutable::now(),
        ));
    }

    protected function handleMedia(object $event, Operation $operation): void
    {
        $usage = $event->response->usage ?? null;

        $this->tracker->record(new UsageData(
            driver: Driver::LaravelAi,
            operation: $operation,
            provider: $this->providerName($event),
            model: $event->model,
            invocationId: $event->invocationId,
            promptTokens: $usage !== null ? TokenAttributes::int($usage, 'promptTokens', 'inputTokens') : 0,
            completionTokens: $usage !== null ? TokenAttributes::int($usage, 'completionTokens', 'outputTokens') : 0,
            latencyMs: $this->latencyFor($event->invocationId),
            startedAt: $this->startTimeFor($event->invocationId),
            endedAt: CarbonImmutable::now(),
        ));
    }

    protected function handleFailover(object $event): void
    {
        $this->tracker->record(new UsageData(
            driver: Driver::LaravelAi,
            operation: Operation::Chat,
            provider: $this->providerName($event),
            model: $event->model ?? null,
            status: UsageStatus::Failed,
            error: method_exists($event->exception, 'getMessage') ? $event->exception->getMessage() : null,
            endedAt: CarbonImmutable::now(),
        ));
    }

    protected function providerName(object $event): ?string
    {
        if (isset($event->response->meta->provider)) {
            return $event->response->meta->provider;
        }

        if (isset($event->provider) && is_object($event->provider) && method_exists($event->provider, 'name')) {
            return $event->provider->name();
        }

        return null;
    }

    protected function latencyFor(string $invocationId): ?int
    {
        if (! isset($this->startedAt[$invocationId])) {
            return null;
        }

        return (int) round((microtime(true) - $this->startedAt[$invocationId]) * 1000);
    }

    protected function startTimeFor(string $invocationId): ?CarbonImmutable
    {
        if (! isset($this->startedAt[$invocationId])) {
            return null;
        }

        $start = CarbonImmutable::createFromTimestampMs((int) round($this->startedAt[$invocationId] * 1000));

        unset($this->startedAt[$invocationId]);

        return $start;
    }
}
