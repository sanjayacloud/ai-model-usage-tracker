<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Support;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

/**
 * Sidebar/header payload shared with Inertia (Vue and React starter kits).
 *
 * @phpstan-type NavigationPayload array{visible: true, url: string, label: string, icon: string}
 */
final class DashboardNavigation
{
    /**
     * @return NavigationPayload|null
     */
    public function payload(): ?array
    {
        if (! config('ai-model-usage-tracker.dashboard.enabled', true)) {
            return null;
        }

        if (! config('ai-model-usage-tracker.dashboard.navigation.enabled', true)) {
            return null;
        }

        if (! Route::has('ai-usage.dashboard')) {
            return null;
        }

        $gate = config('ai-model-usage-tracker.dashboard.gate');

        if (is_string($gate) && $gate !== '') {
            if (! Gate::has($gate) || ! Gate::allows($gate)) {
                return null;
            }
        }

        return [
            'visible' => true,
            'url' => route('ai-usage.dashboard', absolute: false),
            'label' => (string) trans('ai-model-usage-tracker::messages.nav_label'),
            'icon' => (string) config('ai-model-usage-tracker.dashboard.navigation.icon', 'Cpu'),
        ];
    }
}
