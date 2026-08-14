<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Http\Resources;

use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UsageRecord
 */
class UsageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var UsageRecord $record */
        $record = $this->resource;

        return $record->toUsageArray();
    }
}
