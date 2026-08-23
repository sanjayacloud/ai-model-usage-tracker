<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Support;

use Illuminate\Support\Facades\View;
use Inertia\Inertia;

final class DashboardRenderer
{
    public function __construct(
        private StarterKitDetector $starterKit,
    ) {}

    public function driver(): string
    {
        $configured = (string) config('ai-model-usage-tracker.dashboard.driver', 'blade');

        if ($configured !== 'auto') {
            return $configured;
        }

        if ($this->starterKit->isInertia() && class_exists(Inertia::class)) {
            return 'inertia';
        }

        return 'blade';
    }

    public function usesInertia(): bool
    {
        return $this->driver() === 'inertia' && class_exists(Inertia::class);
    }

    public function bladeView(): string
    {
        if (! $this->usesStarterKitShell()) {
            return 'ai-model-usage-tracker::dashboard';
        }

        if (View::exists('components.layouts.app')) {
            return 'ai-model-usage-tracker::dashboard-app';
        }

        if (View::exists('components.app-layout')) {
            return 'ai-model-usage-tracker::dashboard-breeze';
        }

        return 'ai-model-usage-tracker::dashboard';
    }

    public function usesStarterKitShell(): bool
    {
        $layout = (string) config('ai-model-usage-tracker.dashboard.layout', 'auto');

        return match ($layout) {
            'standalone' => false,
            'starter-kit' => true,
            default => $this->starterKit->detect() !== 'none',
        };
    }
}
