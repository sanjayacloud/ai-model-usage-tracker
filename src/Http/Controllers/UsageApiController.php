<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Http\Controllers;

use AiModelUsageTracker\AiModelUsageTracker\Models\UsageRecord;
use AiModelUsageTracker\AiModelUsageTracker\Reporting\UsageReporter;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsageApiController
{
    public function summary(Request $request, UsageReporter $reporter): JsonResponse
    {
        [$from, $to] = $this->range($request);

        return response()->json([
            'totals' => $reporter->totals($from, $to),
            'daily_trend' => $reporter->dailyTrend($from, $to),
            'by_model' => $reporter->byModel($from, $to),
            'by_provider' => $reporter->byProvider($from, $to),
            'by_operation' => $reporter->byOperation($from, $to),
            'top_consumers' => $reporter->topConsumers(10, $from, $to),
        ]);
    }

    public function records(Request $request): JsonResponse
    {
        $records = UsageRecord::query()
            ->latest('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json($records);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function range(Request $request): array
    {
        $days = (int) $request->integer('days', 30);
        $days = $days > 0 ? $days : 30;

        return [
            CarbonImmutable::now()->subDays($days)->startOfDay(),
            CarbonImmutable::now(),
        ];
    }
}
