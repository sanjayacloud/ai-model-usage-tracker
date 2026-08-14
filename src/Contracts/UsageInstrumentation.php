<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Contracts;

interface UsageInstrumentation
{
    /**
     * Whether this driver should be registered (target package installed and
     * the config toggle enabled).
     */
    public function shouldRegister(): bool;

    /**
     * Wire up the listeners/hooks that capture usage automatically.
     */
    public function register(): void;
}
