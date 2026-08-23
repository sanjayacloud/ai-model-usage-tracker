<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Support;

/**
 * Detects official Laravel starter kits from well-known layout files.
 *
 * @phpstan-type StarterKit 'vue'|'react'|'livewire'|'none'
 */
final class StarterKitDetector
{
    public function __construct(
        private string $basePath = '',
    ) {
        if ($this->basePath === '') {
            $this->basePath = base_path();
        }
    }

    /**
     * @return StarterKit
     */
    public function detect(): string
    {
        $configured = (string) config('ai-model-usage-tracker.dashboard.kit', 'auto');

        if (in_array($configured, ['vue', 'react', 'livewire', 'none'], true)) {
            return $configured;
        }

        $vue = $this->exists('resources/js/components/AppSidebar.vue')
            || $this->exists('resources/js/components/AppHeader.vue');
        $react = $this->exists('resources/js/components/app-sidebar.tsx')
            || $this->exists('resources/js/components/app-header.tsx');
        $livewire = $this->exists('resources/views/layouts/app/sidebar.blade.php')
            || $this->exists('resources/views/components/layouts/app/sidebar.blade.php')
            || $this->exists('resources/views/layouts/app/header.blade.php');

        if ($vue && $react) {
            return $this->packageJsonContains('@inertiajs/react') ? 'react' : 'vue';
        }

        if ($vue) {
            return 'vue';
        }

        if ($react) {
            return 'react';
        }

        if ($livewire) {
            return 'livewire';
        }

        return 'none';
    }

    public function isInertia(): bool
    {
        return in_array($this->detect(), ['vue', 'react'], true);
    }

    public function navigationFile(): ?string
    {
        return match ($this->detect()) {
            'vue' => $this->firstExisting([
                'resources/js/components/AppSidebar.vue',
                'resources/js/components/AppHeader.vue',
            ]),
            'react' => $this->firstExisting([
                'resources/js/components/app-sidebar.tsx',
                'resources/js/components/app-header.tsx',
            ]),
            'livewire' => $this->firstExisting([
                'resources/views/layouts/app/sidebar.blade.php',
                'resources/views/components/layouts/app/sidebar.blade.php',
                'resources/views/layouts/app/header.blade.php',
            ]),
            default => null,
        };
    }

    private function exists(string $relative): bool
    {
        return is_file($this->basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative));
    }

    /**
     * @param  list<string>  $relativePaths
     */
    private function firstExisting(array $relativePaths): ?string
    {
        foreach ($relativePaths as $relative) {
            if ($this->exists($relative)) {
                return $this->basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            }
        }

        return null;
    }

    private function packageJsonContains(string $needle): bool
    {
        $path = $this->basePath.DIRECTORY_SEPARATOR.'package.json';

        if (! is_file($path)) {
            return false;
        }

        $json = file_get_contents($path);

        return is_string($json) && str_contains($json, $needle);
    }
}
