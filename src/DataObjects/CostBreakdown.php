<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\DataObjects;

final class CostBreakdown
{
    public function __construct(
        public float $inputCost = 0.0,
        public float $outputCost = 0.0,
        public string $currency = 'USD',
        public bool $pricingFound = false,
    ) {}

    public function totalCost(): float
    {
        return $this->inputCost + $this->outputCost;
    }
}
