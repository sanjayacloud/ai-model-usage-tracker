<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Pricing\Catalog;

interface PricingCatalog
{
    /**
     * @return array<string, array<string, array<string, float>>>
     */
    public function fetch(): array;
}
