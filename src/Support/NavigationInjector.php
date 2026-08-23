<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Support;

final class NavigationInjector
{
    public const string MARKER = 'ai-model-usage-tracker-nav';

    public function alreadyInjected(string $contents): bool
    {
        return str_contains($contents, self::MARKER)
            || str_contains($contents, 'aiUsageNavigation')
            || str_contains($contents, 'route(\'ai-usage.dashboard\')')
            || str_contains($contents, 'route("ai-usage.dashboard")');
    }

    public function inject(string $contents, string $kit): string
    {
        if ($this->alreadyInjected($contents)) {
            return $contents;
        }

        return match ($kit) {
            'vue' => $this->injectVue($contents),
            'react' => $this->injectReact($contents),
            'livewire' => $this->injectLivewire($contents),
            default => $contents,
        };
    }

    private function injectVue(string $contents): string
    {
        $contents = $this->extendNamedImport($contents, 'vue', 'computed');
        $contents = $this->extendNamedImport($contents, '@inertiajs/vue3', 'usePage');
        $lucide = str_contains($contents, '@lucide/vue') ? '@lucide/vue' : 'lucide-vue-next';
        $contents = $this->extendNamedImport($contents, $lucide, 'Cpu');

        if (! str_contains($contents, ':items="mainNavItems"')) {
            return $contents;
        }

        $hook = <<<'JS'

/* ai-model-usage-tracker-nav */
const aiUsageNavItems = computed(() => {
    const nav = usePage().props.aiUsageNavigation as { visible?: boolean; label: string; url: string } | undefined;

    return nav?.visible ? [{ title: nav.label, href: nav.url, icon: Cpu }] : [];
});
JS;

        if (str_contains($contents, '</script>')) {
            $contents = (string) preg_replace('/<\/script>/', $hook."\n</script>", $contents, 1);
        } else {
            $contents .= $hook;
        }

        return str_replace(
            ':items="mainNavItems"',
            ':items="[...mainNavItems, ...aiUsageNavItems]"',
            $contents,
        );
    }

    private function injectReact(string $contents): string
    {
        $contents = $this->extendNamedImport($contents, '@inertiajs/react', 'usePage');
        $contents = $this->extendNamedImport($contents, 'lucide-react', 'Cpu');

        if (! str_contains($contents, 'items={mainNavItems}')) {
            return $contents;
        }

        $hook = <<<'TS'

/* ai-model-usage-tracker-nav */
function useAiUsageNavItems(): NavItem[] {
    const nav = (usePage().props as { aiUsageNavigation?: { visible?: boolean; label: string; url: string } }).aiUsageNavigation;

    return nav?.visible ? [{ title: nav.label, href: nav.url, icon: Cpu }] : [];
}
TS;

        $contents .= $hook;

        if (preg_match('/export (?:default )?function AppSidebar\(/', $contents) === 1) {
            $contents = (string) preg_replace(
                '/(export (?:default )?function AppSidebar\([^)]*\)\s*\{\s*)/',
                '$1'."    const aiUsageItems = useAiUsageNavItems();\n",
                $contents,
                1,
            );
        }

        return str_replace(
            'items={mainNavItems}',
            'items={[...mainNavItems, ...aiUsageItems]}',
            $contents,
        );
    }

    private function injectLivewire(string $contents): string
    {
        $modern = <<<'BLADE'
                    {{-- ai-model-usage-tracker-nav --}}
                    @if (Route::has('ai-usage.dashboard'))
                        <flux:sidebar.item icon="cpu-chip" :href="route('ai-usage.dashboard')" :current="request()->routeIs('ai-usage.dashboard')" wire:navigate>
                            {{ __('AI Usage') }}
                        </flux:sidebar.item>
                    @endif
BLADE;

        if (str_contains($contents, '<flux:sidebar.item') && str_contains($contents, "route('dashboard')")) {
            return (string) preg_replace(
                '/(<flux:sidebar\.item[\s\S]*?route\(\'dashboard\'\)[\s\S]*?<\/flux:sidebar\.item>)/',
                '$1'."\n".$modern,
                $contents,
                1,
            );
        }

        $legacy = <<<'BLADE'
                    {{-- ai-model-usage-tracker-nav --}}
                    @if (Route::has('ai-usage.dashboard'))
                        <flux:navlist.item icon="cpu-chip" :href="route('ai-usage.dashboard')" :current="request()->routeIs('ai-usage.dashboard')" wire:navigate>
                            {{ __('AI Usage') }}
                        </flux:navlist.item>
                    @endif
BLADE;

        if (str_contains($contents, '<flux:navlist.item') && str_contains($contents, "route('dashboard')")) {
            return (string) preg_replace(
                '/(<flux:navlist\.item[\s\S]*?route\(\'dashboard\'\)[\s\S]*?<\/flux:navlist\.item>)/',
                '$1'."\n".$legacy,
                $contents,
                1,
            );
        }

        return $contents;
    }

    private function extendNamedImport(string $contents, string $module, string $name): string
    {
        $pattern = '/import\s*\{([^}]+)\}\s*from\s*([\'"])'.preg_quote($module, '/').'\2/';

        if (preg_match($pattern, $contents, $matches) === 1) {
            if (preg_match('/\b'.preg_quote($name, '/').'\b/', $matches[1]) === 1) {
                return $contents;
            }

            $quote = $matches[2];
            $updated = 'import { '.trim($matches[1], " \t\n\r,").', '.$name.' } from '.$quote.$module.$quote;

            return substr_replace($contents, $updated, (int) strpos($contents, $matches[0]), strlen($matches[0]));
        }

        return 'import { '.$name.' } from \''.$module.'\';'."\n".$contents;
    }
}
