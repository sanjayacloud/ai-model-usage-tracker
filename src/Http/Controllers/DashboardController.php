<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Http\Controllers;

use AiModelUsageTracker\AiModelUsageTracker\Reporting\UsageReporter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    /**
     * @return ViewContract|Response
     */
    public function __invoke(Request $request, UsageReporter $reporter): mixed
    {
        [$from, $to] = $this->range($request);

        $payload = [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'totals' => $reporter->totals($from, $to),
            'dailyTrend' => $reporter->dailyTrend($from, $to),
            'byModel' => $reporter->byModel($from, $to),
            'byProvider' => $reporter->byProvider($from, $to),
            'byOperation' => $reporter->byOperation($from, $to),
            'topConsumers' => $reporter->topConsumers(10, $from, $to),
        ];

        if ($this->usesInertia()) {
            return Inertia::render('AiUsage/Dashboard', $payload);
        }

        return View::make('ai-model-usage-tracker::dashboard', $payload);
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

    protected function usesInertia(): bool
    {
        $driver = (string) config('ai-model-usage-tracker.dashboard.driver', 'blade');

        return $driver === 'inertia' && class_exists(Inertia::class);
    }
}
