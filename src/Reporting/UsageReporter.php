<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Reporting;

use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UsageReporter
{
    /**
     * @return Builder<UsageRecord>
     */
    protected function query(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): Builder
    {
        $query = UsageRecord::query();

        if ($from !== null) {
            $query->where('created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }

    /**
     * @return array{records: int, prompt_tokens: int, completion_tokens: int, total_tokens: int, total_cost: float, failures: int}
     */
    public function totals(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        return $this->totalsFrom($this->query($from, $to));
    }

    /**
     * Per-conversation usage summary (totals plus a per-model breakdown).
     *
     * @return array{totals: array<string, mixed>, by_model: Collection<int, array<string, mixed>>}
     */
    public function forConversation(string $conversationId): array
    {
        return [
            'totals' => $this->totalsFrom(UsageRecord::query()->forConversation($conversationId)),
            'by_model' => UsageRecord::query()
                ->forConversation($conversationId)
                ->groupBy('model')
                ->select('model')
                ->selectRaw('COUNT(*) as records')
                ->selectRaw('SUM(total_tokens) as total_tokens')
                ->selectRaw('SUM(total_cost) as total_cost')
                ->orderByDesc('total_cost')
                ->get()
                ->map(fn (UsageRecord $row): array => $this->mapGroup('model', $row)),
        ];
    }

    /**
     * @param  Builder<UsageRecord>  $query
     * @return array{records: int, prompt_tokens: int, completion_tokens: int, total_tokens: int, total_cost: float, failures: int}
     */
    protected function totalsFrom(Builder $query): array
    {
        $row = $query
            ->selectRaw('COUNT(*) as records')
            ->selectRaw('COALESCE(SUM(prompt_tokens), 0) as prompt_tokens')
            ->selectRaw('COALESCE(SUM(completion_tokens), 0) as completion_tokens')
            ->selectRaw('COALESCE(SUM(total_tokens), 0) as total_tokens')
            ->selectRaw('COALESCE(SUM(total_cost), 0) as total_cost')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END), 0) as failures")
            ->first();

        return [
            'records' => (int) $this->attr($row, 'records'),
            'prompt_tokens' => (int) $this->attr($row, 'prompt_tokens'),
            'completion_tokens' => (int) $this->attr($row, 'completion_tokens'),
            'total_tokens' => (int) $this->attr($row, 'total_tokens'),
            'total_cost' => round((float) $this->attr($row, 'total_cost'), 8),
            'failures' => (int) $this->attr($row, 'failures'),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function byModel(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): Collection
    {
        return $this->grouped('model', $from, $to);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function byProvider(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): Collection
    {
        return $this->grouped('provider', $from, $to);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function byOperation(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): Collection
    {
        return $this->grouped('operation', $from, $to);
    }

    /**
     * Top consumers by attributed model.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function topConsumers(int $limit = 10, ?DateTimeInterface $from = null, ?DateTimeInterface $to = null): Collection
    {
        return $this->query($from, $to)
            ->whereNotNull('trackable_id')
            ->groupBy('trackable_type', 'trackable_id')
            ->selectRaw('trackable_type, trackable_id')
            ->selectRaw('COUNT(*) as records')
            ->selectRaw('SUM(total_tokens) as total_tokens')
            ->selectRaw('SUM(total_cost) as total_cost')
            ->orderByDesc('total_cost')
            ->limit($limit)
            ->get()
            ->map(fn (UsageRecord $row): array => $this->mapConsumer($row));
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapConsumer(UsageRecord $row): array
    {
        return [
            'trackable_type' => $this->attr($row, 'trackable_type'),
            'trackable_id' => $this->attr($row, 'trackable_id'),
            'records' => (int) $this->attr($row, 'records'),
            'total_tokens' => (int) $this->attr($row, 'total_tokens'),
            'total_cost' => round((float) $this->attr($row, 'total_cost'), 8),
        ];
    }

    /**
     * Daily cost/token trend.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function dailyTrend(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): Collection
    {
        return $this->query($from, $to)
            ->groupByRaw('DATE(created_at)')
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as records')
            ->selectRaw('SUM(total_tokens) as total_tokens')
            ->selectRaw('SUM(total_cost) as total_cost')
            ->orderBy('date')
            ->get()
            ->map(fn (UsageRecord $row): array => $this->mapTrend($row));
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapTrend(UsageRecord $row): array
    {
        return [
            'date' => (string) $this->attr($row, 'date'),
            'records' => (int) $this->attr($row, 'records'),
            'total_tokens' => (int) $this->attr($row, 'total_tokens'),
            'total_cost' => round((float) $this->attr($row, 'total_cost'), 8),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function grouped(string $column, ?DateTimeInterface $from, ?DateTimeInterface $to): Collection
    {
        return $this->query($from, $to)
            ->groupBy($column)
            ->select($column)
            ->selectRaw('COUNT(*) as records')
            ->selectRaw('SUM(total_tokens) as total_tokens')
            ->selectRaw('SUM(total_cost) as total_cost')
            ->orderByDesc('total_cost')
            ->get()
            ->map(fn (UsageRecord $row): array => $this->mapGroup($column, $row));
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapGroup(string $column, UsageRecord $row): array
    {
        return [
            $column => $this->scalar($this->attr($row, $column)),
            'records' => (int) $this->attr($row, 'records'),
            'total_tokens' => (int) $this->attr($row, 'total_tokens'),
            'total_cost' => round((float) $this->attr($row, 'total_cost'), 8),
        ];
    }

    protected function attr(?UsageRecord $row, string $key): mixed
    {
        return $row?->getAttribute($key);
    }

    protected function scalar(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
