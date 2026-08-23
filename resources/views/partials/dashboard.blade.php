<div>
    <h1 class="text-xl font-semibold tracking-tight">{{ __('AI Usage') }}</h1>
    <p class="mt-1 text-sm opacity-70">{{ $range['from'] }} – {{ $range['to'] }}</p>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-current/15 p-4">
            <span class="text-sm opacity-70">{{ __('Total cost') }}</span>
            <strong class="mt-1 block text-2xl tabular-nums">{{ number_format($totals['total_cost'], 4) }}</strong>
        </div>
        <div class="rounded-xl border border-current/15 p-4">
            <span class="text-sm opacity-70">{{ __('Requests') }}</span>
            <strong class="mt-1 block text-2xl tabular-nums">{{ number_format($totals['records']) }}</strong>
        </div>
        <div class="rounded-xl border border-current/15 p-4">
            <span class="text-sm opacity-70">{{ __('Total tokens') }}</span>
            <strong class="mt-1 block text-2xl tabular-nums">{{ number_format($totals['total_tokens']) }}</strong>
        </div>
        <div class="rounded-xl border border-current/15 p-4">
            <span class="text-sm opacity-70">{{ __('Failures') }}</span>
            <strong class="mt-1 block text-2xl tabular-nums">{{ number_format($totals['failures']) }}</strong>
        </div>
    </div>

    <h2 class="mt-8 text-sm font-medium">{{ __('By model') }}</h2>
    <table class="mt-2 w-full text-left text-sm">
        <thead>
            <tr>
                <th class="py-2 font-medium opacity-70">{{ __('Model') }}</th>
                <th class="py-2 text-right font-medium opacity-70">{{ __('Requests') }}</th>
                <th class="py-2 text-right font-medium opacity-70">{{ __('Tokens') }}</th>
                <th class="py-2 text-right font-medium opacity-70">{{ __('Cost') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($byModel as $row)
                <tr class="border-t border-current/10">
                    <td class="py-2">{{ $row['model'] ?? 'unknown' }}</td>
                    <td class="py-2 text-right tabular-nums">{{ number_format($row['records']) }}</td>
                    <td class="py-2 text-right tabular-nums">{{ number_format($row['total_tokens']) }}</td>
                    <td class="py-2 text-right tabular-nums">{{ number_format($row['total_cost'], 4) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-2 opacity-70">{{ __('No usage recorded in this range.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="mt-8 text-sm font-medium">{{ __('By provider') }}</h2>
    <table class="mt-2 w-full text-left text-sm">
        <thead>
            <tr>
                <th class="py-2 font-medium opacity-70">{{ __('Provider') }}</th>
                <th class="py-2 text-right font-medium opacity-70">{{ __('Requests') }}</th>
                <th class="py-2 text-right font-medium opacity-70">{{ __('Tokens') }}</th>
                <th class="py-2 text-right font-medium opacity-70">{{ __('Cost') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($byProvider as $row)
                <tr class="border-t border-current/10">
                    <td class="py-2">{{ $row['provider'] ?? 'unknown' }}</td>
                    <td class="py-2 text-right tabular-nums">{{ number_format($row['records']) }}</td>
                    <td class="py-2 text-right tabular-nums">{{ number_format($row['total_tokens']) }}</td>
                    <td class="py-2 text-right tabular-nums">{{ number_format($row['total_cost'], 4) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-2 opacity-70">{{ __('No usage recorded in this range.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
