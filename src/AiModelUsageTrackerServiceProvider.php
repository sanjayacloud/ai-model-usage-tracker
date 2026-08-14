<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker;

use AiModelUsageTracker\AiModelUsageTracker\Console\Commands\PruneUsageCommand;
use AiModelUsageTracker\AiModelUsageTracker\Console\Commands\ReportUsageCommand;
use AiModelUsageTracker\AiModelUsageTracker\Instrumentation\HttpClientInstrumentation;
use AiModelUsageTracker\AiModelUsageTracker\Instrumentation\InstrumentationManager;
use AiModelUsageTracker\AiModelUsageTracker\Instrumentation\LaravelAiInstrumentation;
use AiModelUsageTracker\AiModelUsageTracker\Instrumentation\PrismInstrumentation;
use Illuminate\Support\ServiceProvider;

class AiModelUsageTrackerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai-model-usage-tracker.php', 'ai-model-usage-tracker');

        $this->app->singleton(AiModelUsageTracker::class);
        $this->app->singleton(LaravelAiInstrumentation::class);
        $this->app->singleton(PrismInstrumentation::class);
        $this->app->singleton(HttpClientInstrumentation::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/ai-model-usage-tracker.php');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'ai-model-usage-tracker');

        $this->app->make(InstrumentationManager::class)->boot();

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/ai-model-usage-tracker.php' => config_path('ai-model-usage-tracker.php'),
        ], ['ai-model-usage-tracker', 'ai-model-usage-tracker-config']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/ai-model-usage-tracker'),
        ], ['ai-model-usage-tracker', 'ai-model-usage-tracker-lang']);

        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('js/vendor/ai-model-usage-tracker'),
        ], ['ai-model-usage-tracker', 'ai-model-usage-tracker-assets']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['ai-model-usage-tracker', 'ai-model-usage-tracker-migrations']);

        $this->commands([
            ReportUsageCommand::class,
            PruneUsageCommand::class,
        ]);
    }
}
