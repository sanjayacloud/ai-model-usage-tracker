<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Models;

use AiModelUsageTracker\AiModelUsageTracker\Enums\Driver;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Operation;
use AiModelUsageTracker\AiModelUsageTracker\Enums\UsageStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string|null $invocation_id
 * @property string|null $conversation_id
 * @property Driver $driver
 * @property string|null $provider
 * @property string|null $model
 * @property Operation $operation
 * @property int $prompt_tokens
 * @property int $completion_tokens
 * @property int $cache_write_input_tokens
 * @property int $cache_read_input_tokens
 * @property int $reasoning_tokens
 * @property int $total_tokens
 * @property float $input_cost
 * @property float $output_cost
 * @property float $total_cost
 * @property string $currency
 * @property int|null $latency_ms
 * @property UsageStatus $status
 * @property string|null $error
 * @property bool $streamed
 * @property string|null $trackable_type
 * @property int|string|null $trackable_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UsageRecord extends Model
{
    protected $guarded = [];

    protected $casts = [
        'driver' => Driver::class,
        'operation' => Operation::class,
        'status' => UsageStatus::class,
        'prompt_tokens' => 'int',
        'completion_tokens' => 'int',
        'cache_write_input_tokens' => 'int',
        'cache_read_input_tokens' => 'int',
        'reasoning_tokens' => 'int',
        'total_tokens' => 'int',
        'input_cost' => 'float',
        'output_cost' => 'float',
        'total_cost' => 'float',
        'latency_ms' => 'int',
        'streamed' => 'bool',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setConnection(config('ai-model-usage-tracker.database.connection'));
        $this->setTable(config('ai-model-usage-tracker.database.table', 'ai_usage_records'));
    }

    protected static function booted(): void
    {
        static::creating(function (UsageRecord $record): void {
            if (empty($record->uuid)) {
                $record->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<UsageRecord>  $query
     * @return Builder<UsageRecord>
     */
    public function scopeForModel(Builder $query, string $model): Builder
    {
        return $query->where('model', $model);
    }

    /**
     * @param  Builder<UsageRecord>  $query
     * @return Builder<UsageRecord>
     */
    public function scopeForProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    /**
     * @param  Builder<UsageRecord>  $query
     * @return Builder<UsageRecord>
     */
    public function scopeForInvocation(Builder $query, string $invocationId): Builder
    {
        return $query->where('invocation_id', $invocationId);
    }

    /**
     * @param  Builder<UsageRecord>  $query
     * @return Builder<UsageRecord>
     */
    public function scopeForConversation(Builder $query, string $conversationId): Builder
    {
        return $query->where('conversation_id', $conversationId);
    }

    /**
     * @param  Builder<UsageRecord>  $query
     * @return Builder<UsageRecord>
     */
    public function scopeBetweenDates(Builder $query, mixed $from, mixed $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * @param  Builder<UsageRecord>  $query
     * @return Builder<UsageRecord>
     */
    public function scopeForTrackable(Builder $query, Model $trackable): Builder
    {
        return $query
            ->where('trackable_type', $trackable->getMorphClass())
            ->where('trackable_id', $trackable->getKey());
    }

    /**
     * Compact usage block suitable for embedding in an API response.
     *
     * @return array<string, mixed>
     */
    public function toUsageArray(): array
    {
        return [
            'provider' => $this->provider,
            'model' => $this->model,
            'operation' => $this->operation->value,
            'tokens' => [
                'prompt' => $this->prompt_tokens,
                'completion' => $this->completion_tokens,
                'total' => $this->total_tokens,
            ],
            'cost' => [
                'input' => $this->input_cost,
                'output' => $this->output_cost,
                'total' => $this->total_cost,
                'currency' => $this->currency,
            ],
            'latency_ms' => $this->latency_ms,
        ];
    }
}
