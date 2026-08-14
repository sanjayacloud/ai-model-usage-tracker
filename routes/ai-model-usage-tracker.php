<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\Http\Controllers\DashboardController;
use AiModelUsageTracker\AiModelUsageTracker\Http\Controllers\UsageApiController;
use Illuminate\Support\Facades\Route;

if (! config('ai-model-usage-tracker.dashboard.enabled', true)) {
    return;
}

$path = trim((string) config('ai-model-usage-tracker.dashboard.path', 'ai-usage'), '/');

/** @var array<int, string> $middleware */
$middleware = (array) config('ai-model-usage-tracker.dashboard.middleware', ['web']);

$gate = config('ai-model-usage-tracker.dashboard.gate');

if (is_string($gate) && $gate !== '') {
    $middleware[] = 'can:'.$gate;
}

Route::middleware($middleware)
    ->prefix($path)
    ->name('ai-usage.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/api/summary', [UsageApiController::class, 'summary'])->name('api.summary');
        Route::get('/api/records', [UsageApiController::class, 'records'])->name('api.records');
    });
