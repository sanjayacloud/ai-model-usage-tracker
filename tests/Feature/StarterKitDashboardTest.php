<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\Support\DashboardNavigation;
use AiModelUsageTracker\AiModelUsageTracker\Support\DashboardRenderer;
use AiModelUsageTracker\AiModelUsageTracker\Support\NavigationInjector;
use AiModelUsageTracker\AiModelUsageTracker\Support\StarterKitDetector;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;

function kitRoot(array $files): string
{
    $root = sys_get_temp_dir().'/ai-usage-kit-'.uniqid();

    foreach ($files as $relative => $contents) {
        $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }

    return $root;
}

it('detects vue, react, and livewire starter kits from layout files', function () {
    $vue = kitRoot(['resources/js/components/AppSidebar.vue' => '<template></template>']);
    $react = kitRoot(['resources/js/components/app-sidebar.tsx' => 'export function AppSidebar() {}']);
    $livewire = kitRoot(['resources/views/layouts/app/sidebar.blade.php' => '<flux:sidebar />']);
    $none = kitRoot(['README.md' => 'app']);

    config()->set('ai-model-usage-tracker.dashboard.kit', 'auto');

    expect((new StarterKitDetector($vue))->detect())->toBe('vue')
        ->and((new StarterKitDetector($react))->detect())->toBe('react')
        ->and((new StarterKitDetector($livewire))->detect())->toBe('livewire')
        ->and((new StarterKitDetector($none))->detect())->toBe('none');
});

it('honors an explicit kit config override', function () {
    $root = kitRoot(['resources/js/components/AppSidebar.vue' => '<template></template>']);
    config()->set('ai-model-usage-tracker.dashboard.kit', 'livewire');

    expect((new StarterKitDetector($root))->detect())->toBe('livewire');
});

it('injects a vue sidebar item from the shared navigation prop', function () {
    $source = <<<'VUE'
<script setup lang="ts">
import { LayoutGrid } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
const mainNavItems: NavItem[] = [{ title: 'Dashboard', href: '/dashboard', icon: LayoutGrid }];
</script>
<template>
    <NavMain :items="mainNavItems" />
</template>
VUE;

    $updated = (new NavigationInjector)->inject($source, 'vue');

    expect($updated)->toContain('aiUsageNavigation')
        ->and($updated)->toContain('usePage')
        ->and($updated)->toContain(':items="[...mainNavItems, ...aiUsageNavItems]"')
        ->and(strpos($updated, 'aiUsageNavItems') < strpos($updated, '</script>'))->toBeTrue()
        ->and((new NavigationInjector)->inject($updated, 'vue'))->toBe($updated);
});

it('injects a react sidebar item without calling hooks inside jsx', function () {
    $source = <<<'TSX'
import { LayoutGrid } from 'lucide-react';
import { Link } from '@inertiajs/react';
const mainNavItems: NavItem[] = [{ title: 'Dashboard', href: '/dashboard', icon: LayoutGrid }];
export function AppSidebar() {
    return (
        <NavMain items={mainNavItems} />
    );
}
TSX;

    $updated = (new NavigationInjector)->inject($source, 'react');

    expect($updated)->toContain('const aiUsageItems = useAiUsageNavItems();')
        ->and($updated)->toContain('items={[...mainNavItems, ...aiUsageItems]}')
        ->and($updated)->not->toContain('useAiUsageNavItems()]');
});

it('injects a livewire flux sidebar item after dashboard', function () {
    $source = <<<'BLADE'
<flux:sidebar.nav>
    <flux:sidebar.group :heading="__('Platform')">
        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
            {{ __('Dashboard') }}
        </flux:sidebar.item>
    </flux:sidebar.group>
</flux:sidebar.nav>
BLADE;

    $updated = (new NavigationInjector)->inject($source, 'livewire');

    expect($updated)->toContain("route('ai-usage.dashboard')")
        ->and($updated)->toContain('cpu-chip');
});

it('returns a navigation payload when the gate allows', function () {
    Gate::define('viewAiUsageDashboard', fn (?User $user = null) => true);

    $this->actingAs(dashboardUser());

    $payload = app(DashboardNavigation::class)->payload();

    expect($payload)->toBeArray()
        ->and($payload['visible'])->toBeTrue()
        ->and($payload['label'])->toBe('AI Usage')
        ->and($payload['url'])->toBe('/ai-usage');
});

it('hides navigation when the gate denies', function () {
    Gate::define('viewAiUsageDashboard', fn (?User $user = null) => false);

    $this->actingAs(dashboardUser());

    expect(app(DashboardNavigation::class)->payload())->toBeNull();
});

it('selects inertia when the driver is auto and a vue kit is present', function () {
    $root = kitRoot(['resources/js/components/AppSidebar.vue' => '<template></template>']);
    config()->set('ai-model-usage-tracker.dashboard.driver', 'auto');
    config()->set('ai-model-usage-tracker.dashboard.kit', 'auto');

    $renderer = new DashboardRenderer(new StarterKitDetector($root));

    expect($renderer->usesInertia())->toBeTrue();
});

it('renders the livewire app layout when the starter-kit shell exists', function () {
    config()->set('ai-model-usage-tracker.dashboard.driver', 'blade');
    config()->set('ai-model-usage-tracker.dashboard.layout', 'starter-kit');
    config()->set('ai-model-usage-tracker.dashboard.kit', 'livewire');
    Gate::define('viewAiUsageDashboard', fn (?User $user = null) => true);

    $dir = sys_get_temp_dir().'/ai-usage-views-'.uniqid();
    File::ensureDirectoryExists($dir.'/components/layouts');
    File::put($dir.'/components/layouts/app.blade.php', '<div id="app-shell">{{ $slot }}</div>');
    View::addLocation($dir);

    $this->actingAs(dashboardUser())
        ->get(route('ai-usage.dashboard'))
        ->assertOk()
        ->assertSee('app-shell', false)
        ->assertSee('AI Usage', false);
});

it('runs the install command when no starter kit is present', function () {
    $this->artisan('ai-usage:install', ['--kit' => 'none', '--no-nav' => true])
        ->expectsOutputToContain('No starter kit detected')
        ->assertSuccessful();
});
