<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Tests;

use AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTrackerServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
            AiModelUsageTrackerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('view.paths', array_merge(
            (array) $app['config']->get('view.paths', []),
            [__DIR__.'/stubs/views'],
        ));
        $app['config']->set('inertia.testing.ensure_pages_exist', false);
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
