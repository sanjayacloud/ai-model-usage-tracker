<x-layouts.app :title="__('AI Usage')">
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        @include('ai-model-usage-tracker::partials.dashboard')
    </div>
</x-layouts.app>
