<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Instrumentation;

use AiModelUsageTracker\AiModelUsageTracker\Contracts\UsageInstrumentation;
use Illuminate\Contracts\Container\Container;

class InstrumentationManager
{
    /**
     * @var array<int, class-string<UsageInstrumentation>>
     */
    protected array $drivers = [
        LaravelAiInstrumentation::class,
        PrismInstrumentation::class,
        HttpClientInstrumentation::class,
    ];

    public function __construct(protected Container $container) {}

    public function boot(): void
    {
        foreach ($this->drivers as $driver) {
            /** @var UsageInstrumentation $instance */
            $instance = $this->container->make($driver);

            if ($instance->shouldRegister()) {
                $instance->register();
            }
        }
    }
}
