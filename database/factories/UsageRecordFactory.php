<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Database\Factories;

use AiModelUsageTracker\AiModelUsageTracker\Enums\Driver;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Operation;
use AiModelUsageTracker\AiModelUsageTracker\Enums\UsageStatus;
use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UsageRecord>
 */
class UsageRecordFactory extends Factory
{
    protected $model = UsageRecord::class;

    public function definition(): array
    {
        $prompt = fake()->numberBetween(50, 2000);
        $completion = fake()->numberBetween(20, 800);

        return [
            'uuid' => (string) Str::uuid(),
            'driver' => Driver::Manual,
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'operation' => Operation::Chat,
            'prompt_tokens' => $prompt,
            'completion_tokens' => $completion,
            'total_tokens' => $prompt + $completion,
            'input_cost' => 0,
            'output_cost' => 0,
            'total_cost' => 0,
            'currency' => 'USD',
            'status' => UsageStatus::Success,
            'streamed' => false,
        ];
    }

    public function missingPricing(): static
    {
        return $this->state(fn (): array => [
            'model' => 'unknown-model',
            'metadata' => ['pricing_missing' => true],
        ]);
    }
}
