<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Master Switch
    |--------------------------------------------------------------------------
    |
    | When disabled, no usage is recorded regardless of the driver or manual
    | calls. Useful for turning the tracker off in certain environments.
    |
    */

    'enabled' => env('AI_USAGE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Recording
    |--------------------------------------------------------------------------
    |
    | "mode" controls how usage rows are persisted:
    |   - "sync":  written immediately in the request lifecycle (always exact).
    |   - "queue": dispatched to a queued job for high-throughput apps.
    |
    */

    'recording' => [
        'mode' => env('AI_USAGE_RECORDING_MODE', 'sync'),
        'queue' => [
            'connection' => env('AI_USAGE_QUEUE_CONNECTION'),
            'queue' => env('AI_USAGE_QUEUE', 'default'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    */

    'database' => [
        'connection' => env('AI_USAGE_DB_CONNECTION'),
        'table' => 'ai_usage_records',
    ],

    /*
    |--------------------------------------------------------------------------
    | Attribution
    |--------------------------------------------------------------------------
    |
    | Resolves the "owner" of a usage record when one is not supplied
    | explicitly. Set to a closure via AiModelUsageTracker::resolveTrackableUsing()
    | or leave as "auth" to attribute to the authenticated user.
    |
    */

    'attribution' => [
        'default' => env('AI_USAGE_ATTRIBUTION', 'auth'),
        'guard' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Instrumentation
    |--------------------------------------------------------------------------
    |
    | Toggle the pluggable drivers that automatically capture usage. Each
    | driver only registers when its target package is installed.
    |
    */

    'instrumentation' => [
        'laravel-ai' => env('AI_USAGE_INSTRUMENT_LARAVEL_AI', true),
        'prism' => env('AI_USAGE_INSTRUMENT_PRISM', false),
        'http' => env('AI_USAGE_INSTRUMENT_HTTP', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    |
    | Rates are expressed per 1,000,000 tokens in the currency below. Costs are
    | only computed for models present here; unknown models are recorded with a
    | zero cost and a "pricing_missing" flag in metadata so they are auditable.
    |
    | Structure: 'provider' => ['model' => [
    |     'input', 'output', 'cache_write', 'cache_read', 'reasoning',
    | ]]
    |
    */

    'pricing' => [
        'currency' => env('AI_USAGE_CURRENCY', 'USD'),
        'models' => [
            'openai' => [
                'gpt-4o' => ['input' => 2.50, 'output' => 10.00, 'cache_read' => 1.25],
                'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60, 'cache_read' => 0.075],
                'gpt-4.1' => ['input' => 2.00, 'output' => 8.00, 'cache_read' => 0.50],
                'gpt-4.1-mini' => ['input' => 0.40, 'output' => 1.60, 'cache_read' => 0.10],
                'text-embedding-3-small' => ['input' => 0.02, 'output' => 0.00],
                'text-embedding-3-large' => ['input' => 0.13, 'output' => 0.00],
            ],
            'anthropic' => [
                'claude-3-5-sonnet-latest' => ['input' => 3.00, 'output' => 15.00, 'cache_write' => 3.75, 'cache_read' => 0.30],
                'claude-3-5-haiku-latest' => ['input' => 0.80, 'output' => 4.00, 'cache_write' => 1.00, 'cache_read' => 0.08],
                'claude-3-opus-latest' => ['input' => 15.00, 'output' => 75.00, 'cache_write' => 18.75, 'cache_read' => 1.50],
            ],
            'gemini' => [
                'gemini-1.5-pro' => ['input' => 1.25, 'output' => 5.00, 'cache_read' => 0.3125],
                'gemini-1.5-flash' => ['input' => 0.075, 'output' => 0.30, 'cache_read' => 0.01875],
                'gemini-2.0-flash' => ['input' => 0.10, 'output' => 0.40],
                'gemini-2.5-flash-lite' => ['input' => 0.10, 'output' => 0.40, 'cache_read' => 0.01],
                'gemini-3.1-flash-lite' => ['input' => 0.25, 'output' => 1.50, 'cache_read' => 0.025],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Budgets
    |--------------------------------------------------------------------------
    |
    | Optional spending caps. When accumulated cost within a period crosses a
    | threshold, a BudgetThresholdReached event is dispatched.
    |
    | Each budget: ['period' => 'day|week|month', 'limit' => 100.0,
    |               'thresholds' => [0.8, 1.0], 'notify' => null]
    |
    */

    'budgets' => [],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to keep usage records. Null keeps records forever. Use the
    | "ai-usage:prune" command (schedule it) to apply retention.
    |
    */

    'retention_days' => env('AI_USAGE_RETENTION_DAYS'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    |
    | The Inertia/Vue dashboard. Requires the host app to use Inertia + Vue.
    | Access is protected by the "gate" ability; define it in a policy or via
    | Gate::define(). When "enabled" is false no routes are registered.
    |
    */

    'dashboard' => [
        'enabled' => env('AI_USAGE_DASHBOARD_ENABLED', true),
        'path' => env('AI_USAGE_DASHBOARD_PATH', 'ai-usage'),
        'middleware' => ['web'],
        'gate' => 'viewAiUsageDashboard',
    ],

];
