<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Console\Commands;

use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class PruneUsageCommand extends Command
{
    protected $signature = 'ai-usage:prune {--days= : Override the configured retention window}';

    protected $description = 'Delete AI usage records older than the retention window.';

    public function handle(): int
    {
        $days = $this->option('days') ?? config('ai-model-usage-tracker.retention_days');

        if ($days === null) {
            $this->warn('No retention window configured; nothing pruned.');

            return self::SUCCESS;
        }

        $cutoff = CarbonImmutable::now()->subDays((int) $days);

        $deleted = UsageRecord::query()->where('created_at', '<', $cutoff)->delete();

        $this->info(sprintf('Pruned %d usage record(s) older than %d day(s).', $deleted, (int) $days));

        return self::SUCCESS;
    }
}
