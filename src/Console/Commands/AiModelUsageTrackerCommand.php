<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Console\Commands;

use Illuminate\Console\Command;

class AiModelUsageTrackerCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'ai-model-usage-tracker:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package ai-model-usage-tracker.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('AiModelUsageTracker placeholder command executed.');

        return self::SUCCESS;
    }
}
