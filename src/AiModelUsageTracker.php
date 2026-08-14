<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker;

use AiModelUsageTracker\AiModelUsageTracker\Budgets\BudgetGuard;
use AiModelUsageTracker\AiModelUsageTracker\DataObjects\UsageData;
use AiModelUsageTracker\AiModelUsageTracker\Events\UsageRecorded;
use AiModelUsageTracker\AiModelUsageTracker\Instrumentation\PrismInstrumentation;
use AiModelUsageTracker\AiModelUsageTracker\Jobs\RecordUsageJob;
use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;
use AiModelUsageTracker\AiModelUsageTracker\Pricing\CostCalculator;
use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AiModelUsageTracker
{
    protected ?Closure $trackableResolver = null;

    public function __construct(
        protected Config $config,
        protected CostCalculator $costs,
        protected BudgetGuard $budgets,
    ) {}

    public function enabled(): bool
    {
        return (bool) $this->config->get('ai-model-usage-tracker.enabled', true);
    }

    /**
     * Override how the owning model of a usage record is resolved.
     */
    public function resolveTrackableUsing(Closure $resolver): void
    {
        $this->trackableResolver = $resolver;
    }

    /**
     * Begin a fluent usage record attributed to the given model.
     */
    public function for(Model $trackable): PendingUsage
    {
        return (new PendingUsage($this))->for($trackable);
    }

    /**
     * Begin a fluent usage record with no explicit owner.
     */
    public function track(): PendingUsage
    {
        return new PendingUsage($this);
    }

    /**
     * Record a usage entry, either synchronously or via the queue.
     */
    public function record(UsageData $data): ?UsageRecord
    {
        if (! $this->enabled()) {
            return null;
        }

        $this->applyAttribution($data);

        if ($this->config->get('ai-model-usage-tracker.recording.mode') === 'queue') {
            $connection = $this->config->get('ai-model-usage-tracker.recording.queue.connection');
            $queue = $this->config->get('ai-model-usage-tracker.recording.queue.queue', 'default');

            RecordUsageJob::dispatch($data->toArray())
                ->onConnection($connection)
                ->onQueue($queue);

            return null;
        }

        return $this->persist($data);
    }

    /**
     * Compute cost and write the record. Shared by sync and queued paths.
     */
    public function persist(UsageData $data): UsageRecord
    {
        $cost = $this->costs->calculate($data);

        $metadata = $data->metadata;

        if (! $cost->pricingFound) {
            $metadata['pricing_missing'] = true;
        }

        $record = new UsageRecord;
        $record->fill([
            'invocation_id' => $data->invocationId,
            'conversation_id' => $data->conversationId,
            'driver' => $data->driver,
            'provider' => $data->provider,
            'model' => $data->model,
            'operation' => $data->operation,
            'prompt_tokens' => $data->promptTokens,
            'completion_tokens' => $data->completionTokens,
            'cache_write_input_tokens' => $data->cacheWriteInputTokens,
            'cache_read_input_tokens' => $data->cacheReadInputTokens,
            'reasoning_tokens' => $data->reasoningTokens,
            'total_tokens' => $data->totalTokens(),
            'input_cost' => $cost->inputCost,
            'output_cost' => $cost->outputCost,
            'total_cost' => $cost->totalCost(),
            'currency' => $cost->currency,
            'latency_ms' => $data->latencyMs,
            'status' => $data->status,
            'error' => $data->error,
            'streamed' => $data->streamed,
            'trackable_type' => $data->trackableType,
            'trackable_id' => $data->trackableId,
            'metadata' => $metadata === [] ? null : $metadata,
            'started_at' => $data->startedAt,
            'ended_at' => $data->endedAt,
        ]);
        $record->save();

        UsageRecorded::dispatch($record);

        $this->budgets->evaluate($record);

        return $record;
    }

    /**
     * Capture usage from a Prism response object.
     */
    public function capturePrism(object $response, ?Model $trackable = null): ?UsageRecord
    {
        return app(PrismInstrumentation::class)->capture($response, $trackable);
    }

    /**
     * Fetch the recorded usage for a specific AI request.
     */
    public function forInvocation(string $invocationId): ?UsageRecord
    {
        return UsageRecord::query()
            ->forInvocation($invocationId)
            ->latest('id')
            ->first();
    }

    /**
     * Fetch the most recently recorded usage entry.
     */
    public function latest(): ?UsageRecord
    {
        return UsageRecord::query()->latest('id')->first();
    }

    protected function applyAttribution(UsageData $data): void
    {
        if ($data->trackableType !== null) {
            return;
        }

        $trackable = $this->resolveTrackable();

        if ($trackable instanceof Model) {
            $data->trackableType = $trackable->getMorphClass();
            $data->trackableId = $trackable->getKey();
        }
    }

    protected function resolveTrackable(): ?Model
    {
        if ($this->trackableResolver !== null) {
            $resolved = ($this->trackableResolver)();

            return $resolved instanceof Model ? $resolved : null;
        }

        if ($this->config->get('ai-model-usage-tracker.attribution.default') !== 'auth') {
            return null;
        }

        $guard = $this->config->get('ai-model-usage-tracker.attribution.guard');
        $user = Auth::guard($guard)->user();

        return $user instanceof Model ? $user : null;
    }
}
