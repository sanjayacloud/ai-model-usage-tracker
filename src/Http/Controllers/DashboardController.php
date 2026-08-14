<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Http\Controllers;

use AiModelUsageTracker\AiModelUsageTracker\Reporting\UsageReporter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function __invoke(Request $request, UsageReporter $reporter): Response
    {
        [$from, $to] = $this->range($request);

        return Inertia::render('AiUsage/Dashboard', [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'totals' => $reporter->totals($from, $to),
            'dailyTrend' => $reporter->dailyTrend($from, $to),
            'byModel' => $reporter->byModel($from, $to),
            'byProvider' => $reporter->byProvider($from, $to),
            'byOperation' => $reporter->byOperation($from, $to),
            'topConsumers' => $reporter->topConsumers(10, $from, $to),
        ]);
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
