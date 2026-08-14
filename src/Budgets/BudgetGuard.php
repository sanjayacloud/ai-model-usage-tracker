<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Budgets;

use AiModelUsageTracker\AiModelUsageTracker\Events\BudgetThresholdReached;
use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository as Config;

class BudgetGuard
{
    public function __construct(protected Config $config) {}

    /**
     * Evaluate configured budgets after a record is written, dispatching a
     * BudgetThresholdReached event the moment a threshold is first crossed.
     */
    public function evaluate(UsageRecord $record): void
    {
        /** @var array<string, array<string, mixed>> $budgets */
        $budgets = $this->config->get('ai-model-usage-tracker.budgets', []);

        foreach ($budgets as $name => $budget) {
            $this->evaluateBudget((string) $name, $budget);
        }
    }

    /**
     * @param  array<string, mixed>  $budget
     */
    protected function evaluateBudget(string $name, array $budget): void
    {
        $limit = (float) ($budget['limit'] ?? 0);

        if ($limit <= 0) {
            return;
        }

        $spend = $this->spendSince($this->periodStart((string) ($budget['period'] ?? 'month')));

        /** @var array<int, float> $thresholds */
        $thresholds = $budget['thresholds'] ?? [1.0];

        foreach ($thresholds as $threshold) {
            $target = $limit * (float) $threshold;

            if ($spend >= $target) {
                BudgetThresholdReached::dispatch($name, $budget, $spend, $limit, (float) $threshold);
            }
        }
    }

    protected function periodStart(string $period): CarbonImmutable
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'day' => $now->startOfDay(),
            'week' => $now->startOfWeek(),
            default => $now->startOfMonth(),
        };
    }

    protected function spendSince(CarbonImmutable $since): float
    {
        return (float) UsageRecord::query()
            ->where('created_at', '>=', $since)
            ->sum('total_cost');
    }
}
