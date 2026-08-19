<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI usage</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 2rem auto; max-width: 960px; padding: 0 1rem; }
        h1 { font-size: 1.5rem; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
        .card { border: 1px solid color-mix(in srgb, currentColor 20%, transparent); border-radius: 8px; padding: 1rem; }
        .card span { display: block; font-size: 0.8rem; opacity: 0.7; }
        .card strong { font-size: 1.15rem; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0 2rem; }
        th, td { text-align: left; padding: 0.4rem 0.5rem; border-bottom: 1px solid color-mix(in srgb, currentColor 15%, transparent); }
        th { font-size: 0.8rem; opacity: 0.7; }
        .muted { opacity: 0.7; font-size: 0.9rem; }
    </style>
</head>
<body>
    <h1>AI usage</h1>
    <p class="muted">{{ $range['from'] }} – {{ $range['to'] }}</p>

    <div class="cards">
        <div class="card"><span>Records</span><strong>{{ number_format($totals['records']) }}</strong></div>
        <div class="card"><span>Tokens</span><strong>{{ number_format($totals['total_tokens']) }}</strong></div>
        <div class="card"><span>Cost</span><strong>{{ number_format($totals['total_cost'], 6) }}</strong></div>
        <div class="card"><span>Failures</span><strong>{{ number_format($totals['failures']) }}</strong></div>
    </div>

    <h2>By model</h2>
    <table>
        <thead>
            <tr><th>Model</th><th>Records</th><th>Tokens</th><th>Cost</th></tr>
        </thead>
        <tbody>
            @forelse ($byModel as $row)
                <tr>
                    <td>{{ $row['model'] ?? 'unknown' }}</td>
                    <td>{{ number_format($row['records']) }}</td>
                    <td>{{ number_format($row['total_tokens']) }}</td>
                    <td>{{ number_format($row['total_cost'], 6) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">No usage recorded in this range.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>By provider</h2>
    <table>
        <thead>
            <tr><th>Provider</th><th>Records</th><th>Tokens</th><th>Cost</th></tr>
        </thead>
        <tbody>
            @forelse ($byProvider as $row)
                <tr>
                    <td>{{ $row['provider'] ?? 'unknown' }}</td>
                    <td>{{ number_format($row['records']) }}</td>
                    <td>{{ number_format($row['total_tokens']) }}</td>
                    <td>{{ number_format($row['total_cost'], 6) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">No usage recorded in this range.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
