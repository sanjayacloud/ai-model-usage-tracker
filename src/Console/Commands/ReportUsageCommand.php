<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Console\Commands;

use AiModelUsageTracker\AiModelUsageTracker\Reporting\UsageReporter;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ReportUsageCommand extends Command
{
    protected $signature = 'ai-usage:report {--days=30 : Number of days to include}';

    protected $description = 'Summarize recorded AI model usage and cost.';

    public function handle(UsageReporter $reporter): int
    {
        $from = CarbonImmutable::now()->subDays((int) $this->option('days'))->startOfDay();
        $to = CarbonImmutable::now();

        $totals = $reporter->totals($from, $to);

        $this->info(sprintf('AI usage for the last %d day(s):', (int) $this->option('days')));
        $this->line(sprintf('  Requests:   %d (%d failed)', $totals['records'], $totals['failures']));
        $this->line(sprintf('  Tokens:     %d (prompt %d / completion %d)', $totals['total_tokens'], $totals['prompt_tokens'], $totals['completion_tokens']));
        $this->line(sprintf('  Total cost: %.4f', $totals['total_cost']));

        $byModel = $reporter->byModel($from, $to);

        if ($byModel->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['Model', 'Requests', 'Tokens', 'Cost'],
                $byModel->map(fn (array $row) => [
                    $row['model'] ?? '(unknown)',
                    $row['records'],
                    $row['total_tokens'],
                    number_format($row['total_cost'], 4),
                ])->all(),
            );
        }

        return self::SUCCESS;
    }
}
