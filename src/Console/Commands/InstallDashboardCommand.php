<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Console\Commands;

use AiModelUsageTracker\AiModelUsageTracker\Support\NavigationInjector;
use AiModelUsageTracker\AiModelUsageTracker\Support\StarterKitDetector;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallDashboardCommand extends Command
{
    protected $signature = 'ai-usage:install
        {--kit=auto : vue, react, livewire, none, or auto}
        {--no-nav : Skip adding a sidebar / header nav item}
        {--force : Overwrite an existing published Inertia page}';

    protected $description = 'Publish the AI usage dashboard into a Laravel starter kit (Vue, React, or Livewire).';

    public function handle(Filesystem $files, NavigationInjector $injector): int
    {
        $basePath = base_path();
        $kitOption = (string) $this->option('kit');

        if ($kitOption !== 'auto') {
            config()->set('ai-model-usage-tracker.dashboard.kit', $kitOption);
        }

        $detector = new StarterKitDetector($basePath);
        $kit = $detector->detect();

        $this->info(sprintf('Detected starter kit: %s', $kit));

        if (in_array($kit, ['vue', 'react'], true)) {
            $this->publishInertiaPage($files, $kit);
            $this->comment('Set AI_USAGE_DASHBOARD_DRIVER=inertia (or dashboard.driver = inertia) and rebuild frontend assets.');
        } elseif ($kit === 'livewire') {
            $this->comment('Set AI_USAGE_DASHBOARD_LAYOUT=starter-kit (or dashboard.layout = starter-kit) so /ai-usage uses the Livewire app shell.');
        } else {
            $this->comment('No starter kit detected. The standalone Blade dashboard remains at /ai-usage.');
        }

        if (! $this->option('no-nav') && $kit !== 'none') {
            $this->injectNavigation($files, $injector, $detector, $kit);
        }

        $this->newLine();
        $this->line('Define the dashboard gate if you have not already:');
        $this->line("    Gate::define('viewAiUsageDashboard', fn (\$user) => \$user->is_admin);");

        return self::SUCCESS;
    }

    private function publishInertiaPage(Filesystem $files, string $kit): void
    {
        $extension = $kit === 'react' ? 'tsx' : 'vue';
        $source = dirname(__DIR__, 2).'/resources/js/pages/AiUsage/Dashboard.'.$extension;
        $destination = resource_path('js/pages/AiUsage/Dashboard.'.$extension);

        if (! $files->exists($source)) {
            $this->error("Missing packaged page at {$source}");

            return;
        }

        if ($files->exists($destination) && ! $this->option('force')) {
            $this->comment("Inertia page already exists: {$destination} (use --force to replace)");

            return;
        }

        $files->ensureDirectoryExists(dirname($destination));
        $files->copy($source, $destination);
        $this->info("Published {$destination}");
    }

    private function injectNavigation(Filesystem $files, NavigationInjector $injector, StarterKitDetector $detector, string $kit): void
    {
        $path = $detector->navigationFile();

        if ($path === null || ! $files->exists($path)) {
            $this->warn('Could not find a starter-kit navigation file to update.');
            $this->comment('Add the item from README (aiUsageNavigation Inertia prop, or route(\'ai-usage.dashboard\')).');

            return;
        }

        $contents = $files->get($path);

        if ($injector->alreadyInjected($contents)) {
            $this->comment("Navigation already includes AI Usage: {$path}");

            return;
        }

        $updated = $injector->inject($contents, $kit);

        if ($updated === $contents) {
            $this->warn("Could not automatically patch {$path}. Add the nav snippet from the README.");

            return;
        }

        $files->put($path, $updated);
        $this->info("Added AI Usage to {$path}");
    }
}
