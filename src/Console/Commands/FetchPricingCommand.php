<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Console\Commands;

use AiModelUsageTracker\AiModelUsageTracker\Pricing\CatalogManager;
use Illuminate\Console\Command;

class FetchPricingCommand extends Command
{
    protected $signature = 'ai-usage:fetch-pricing {--force : Refresh even when a fresh cache exists}';

    protected $description = 'Download model pricing from LiteLLM (OpenRouter fallback) into cache.';

    public function handle(CatalogManager $catalogs): int
    {
        $result = $catalogs->refresh((bool) $this->option('force'));

        if ($result['skipped'] && $result['source'] === 'disabled') {
            $this->warn('Pricing fetch is disabled in config.');

            return self::SUCCESS;
        }

        if ($result['skipped']) {
            $this->info(sprintf('Using cached catalog (%d model(s), source: %s).', $result['models'], $result['source']));

            return self::SUCCESS;
        }

        $this->info(sprintf('Stored %d model rate(s) from %s.', $result['models'], $result['source']));

        return self::SUCCESS;
    }
}
