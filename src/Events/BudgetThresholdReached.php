<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Events;

use Illuminate\Foundation\Events\Dispatchable;

class BudgetThresholdReached
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $budget
     */
    public function __construct(
        public string $name,
        public array $budget,
        public float $spend,
        public float $limit,
        public float $threshold,
    ) {}
}
