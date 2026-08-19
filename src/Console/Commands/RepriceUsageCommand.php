<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Console\Commands;

use AiModelUsageTracker\AiModelUsageTracker\DataObjects\UsageData;
use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;
use AiModelUsageTracker\AiModelUsageTracker\Pricing\CostCalculator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class RepriceUsageCommand extends Command
{
    protected $signature = 'ai-usage:reprice
        {--missing-only : Only rows with zero cost or pricing_missing (default)}
        {--all : Reprice every record}
        {--conversation= : Limit to a laravel/ai conversation id}
        {--dry-run : Show how many rows would change without writing}';

    protected $description = 'Recompute stored usage costs from current pricing rates.';

    public function handle(CostCalculator $calculator): int
    {
        $query = $this->query();
        $updated = 0;
        $unchanged = 0;
        $stillMissing = 0;
        $dryRun = (bool) $this->option('dry-run');

        $query->each(function (UsageRecord $record) use ($calculator, $dryRun, &$updated, &$unchanged, &$stillMissing): void {
            $cost = $calculator->calculate($this->toUsageData($record));
            $metadata = $record->metadata ?? [];

            if (! $cost->pricingFound) {
                $stillMissing++;

                return;
            }

            unset($metadata['pricing_missing']);

            $same = abs($record->total_cost - $cost->totalCost()) < 0.00000001
                && abs($record->input_cost - $cost->inputCost) < 0.00000001
                && abs($record->output_cost - $cost->outputCost) < 0.00000001
                && ! isset($record->metadata['pricing_missing']);

            if ($same) {
                $unchanged++;

                return;
            }

            $updated++;

            if ($dryRun) {
                return;
            }

            $record->forceFill([
                'input_cost' => $cost->inputCost,
                'output_cost' => $cost->outputCost,
                'total_cost' => $cost->totalCost(),
                'currency' => $cost->currency,
                'metadata' => $metadata === [] ? null : $metadata,
            ])->saveQuietly();
        });

        $this->info(sprintf(
            '%s%d updated, %d unchanged, %d still missing pricing.',
            $dryRun ? 'Dry run: ' : '',
            $updated,
            $unchanged,
            $stillMissing,
        ));

        return self::SUCCESS;
    }

    /**
     * @return Builder<UsageRecord>
     */
    protected function query(): Builder
    {
        $query = UsageRecord::query();

        if ($this->option('conversation')) {
            $query->forConversation((string) $this->option('conversation'));
        }

        if (! $this->option('all')) {
            $query->where(function (Builder $inner): void {
                $inner->where('total_cost', 0)
                    ->orWhere('metadata->pricing_missing', true);
            });
        }

        return $query;
    }

    protected function toUsageData(UsageRecord $record): UsageData
    {
        return new UsageData(
            driver: $record->driver,
            operation: $record->operation,
            provider: $record->provider,
            model: $record->model,
            promptTokens: $record->prompt_tokens,
            completionTokens: $record->completion_tokens,
            cacheWriteInputTokens: $record->cache_write_input_tokens,
            cacheReadInputTokens: $record->cache_read_input_tokens,
            reasoningTokens: $record->reasoning_tokens,
            metadata: $record->metadata ?? [],
        );
    }
}
