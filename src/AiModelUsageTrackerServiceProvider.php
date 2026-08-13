<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker;

use AiModelUsageTracker\AiModelUsageTracker\Console\Commands\AiModelUsageTrackerCommand;
use Illuminate\Support\ServiceProvider;

class AiModelUsageTrackerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai-model-usage-tracker.php', 'ai-model-usage-tracker');

        $this->app->singleton(AiModelUsageTracker::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/ai-model-usage-tracker.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ai-model-usage-tracker');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'ai-model-usage-tracker');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/ai-model-usage-tracker.php' => config_path('ai-model-usage-tracker.php'),
        ], ['ai-model-usage-tracker', 'ai-model-usage-tracker-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/ai-model-usage-tracker'),
        ], ['ai-model-usage-tracker', 'ai-model-usage-tracker-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/ai-model-usage-tracker'),
        ], ['ai-model-usage-tracker', 'ai-model-usage-tracker-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/ai-model-usage-tracker'),
        ], ['ai-model-usage-tracker', 'ai-model-usage-tracker-assets']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['ai-model-usage-tracker', 'ai-model-usage-tracker-migrations']);

        $this->commands([
            AiModelUsageTrackerCommand::class,
        ]);
    }
}
