<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Tests;

use AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTrackerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AiModelUsageTrackerServiceProvider::class,
        ];
    }
}
