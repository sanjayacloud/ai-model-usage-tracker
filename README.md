<div align="center">
    <h1>AI Model Usage Tracker</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/sanjayacloud/ai-model-usage-tracker"><img src="https://img.shields.io/packagist/v/sanjayacloud/ai-model-usage-tracker.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/sanjayacloud/ai-model-usage-tracker"><img src="https://img.shields.io/packagist/php-v/sanjayacloud/ai-model-usage-tracker.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://github.com/sanjayacloud/ai-model-usage-tracker/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/sanjayacloud/ai-model-usage-tracker/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/sanjayacloud/ai-model-usage-tracker"><img src="https://img.shields.io/packagist/dt/sanjayacloud/ai-model-usage-tracker.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Accurately track AI model usage in your Laravel app: token counts, **computed cost**, latency, success/failure, per-user attribution, and per-conversation attribution. Capture usage automatically from the first-party [`laravel/ai`](https://github.com/laravel/ai) SDK (plus Prism and raw HTTP clients), or record it manually with a fluent API. Includes a headless reporting layer, budget alerts, auto-fetched pricing, and a dashboard that can sit inside the official Laravel Vue, React, or Livewire starter kits.

## Features

- **Automatic capture** for the `laravel/ai` SDK — no code changes required.
- **Computed cost** from a configurable per-model pricing table (input, output, cache read/write, reasoning, per-image, per-second).
- **Prefix matching** so dated model ids (`gpt-4o-2024-11-20`) resolve to catalog rates.
- **Auto-fetched pricing** from LiteLLM (OpenRouter fallback) via `ai-usage:fetch-pricing` — never on the request path.
- **Per-request, per-user, per-conversation, and feature-key** attribution.
- **Reporting API** — totals, breakdowns by model/provider/operation, daily trends, top consumers.
- **Budgets** with threshold events, **retention** pruning, and a **dashboard** (Blade by default; Vue / React / Livewire starter-kit shells optional).
- **Sync or queued** persistence.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- (Optional) [`laravel/ai`](https://github.com/laravel/ai) for automatic instrumentation
- (Optional) Inertia + Vue or React, or the Livewire starter kit, to embed the dashboard in the app layout

## How it works

Every AI request becomes one row in the `ai_usage_records` table, written through a single pipeline regardless of how it was captured:

```
manual API ─┐
laravel/ai ─┤
Prism      ─┼─▶ UsageTracker ─▶ CostCalculator ─▶ (sync | queue) ─▶ ai_usage_records
raw HTTP   ─┘
```

---

## Getting started

### Step 1 — Install

```bash
composer require sanjayacloud/ai-model-usage-tracker
```

The service provider and `AiModelUsageTracker` facade are auto-discovered (`UsageTracker` is an alias).

### Step 2 — Run the migration

```bash
php artisan migrate
```

Package migrations load automatically. Publishing them is optional — do **not** `vendor:publish --force` later or you will duplicate the create-table migration.

```bash
php artisan vendor:publish --tag="ai-model-usage-tracker-migrations"
```

### Step 3 — Publish the config (recommended)

```bash
php artisan vendor:publish --tag="ai-model-usage-tracker-config"
```

This writes `config/ai-model-usage-tracker.php`, where you control pricing, recording mode, instrumentation, budgets, retention, and the dashboard.

### Step 4 — Capture your first usage

If you use `laravel/ai`, you're already done — the next agent prompt or embedding call is recorded automatically (see [Automatic capture](#automatic-capture-laravelai)).

To record manually from anywhere:

```php
use AiModelUsageTracker\AiModelUsageTracker\Facades\AiModelUsageTracker;

AiModelUsageTracker::track()
    ->provider('openai')
    ->model('gpt-4o')
    ->tokens(prompt: 1200, completion: 350)
    ->record();
```

### Step 5 — Read it back

```php
use AiModelUsageTracker\AiModelUsageTracker\Reporting\UsageReporter;

app(UsageReporter::class)->totals(); // ['records' => 1, 'total_tokens' => 1550, 'total_cost' => 0.0065, ...]
```

That's the full loop: capture → cost → report.

### Upgrading from 1.0

```bash
composer update sanjayacloud/ai-model-usage-tracker
php artisan migrate
```

Then schedule catalog refresh and reprice any rows that were recorded at `$0`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('ai-usage:fetch-pricing')->daily();
```

```bash
php artisan ai-usage:fetch-pricing
php artisan ai-usage:reprice
```

If you already published migrations in 1.0, do **not** republish with `--force`. Package migrations load automatically and skip work when the table or columns already exist (so a second `create` will not fail). The new `feature_key` column is added on `php artisan migrate`.

The dashboard now defaults to Blade. Set `AI_USAGE_DASHBOARD_DRIVER=inertia` only if you already published the Vue page.

---

## Capturing usage

### Automatic capture (laravel/ai)

Enabled by default. The package listens to the SDK's events (`AgentPrompted`, `AgentStreamed`, `EmbeddingsGenerated`, `ImageGenerated`, `AudioGenerated`, `TranscriptionGenerated`, `ProviderFailedOver`) and records tokens, model, provider, latency, failures — and the conversation id when the call is part of a `laravel/ai` conversation.

Toggle it in config:

```php
'instrumentation' => [
    'laravel-ai' => true,
    'prism' => false,
    'http' => false,
],
```

### Manual API (fluent builder)

```php
use AiModelUsageTracker\AiModelUsageTracker\Facades\AiModelUsageTracker;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Operation;

AiModelUsageTracker::for($user) // attribute to any Eloquent model
    ->provider('openai')
    ->model('gpt-4o')
    ->operation(Operation::Chat)
    ->tokens(prompt: 1200, completion: 350, cacheRead: 800, reasoning: 120)
    ->conversation($conversationId) // optional, for per-conversation reporting
    ->feature('support-bot')        // optional, for grouping by product feature
    ->latency(840)
    ->meta(['ticket_id' => 42])
    ->record();
```

Available builder methods: `provider()`, `model()`, `operation()`, `tokens()`, `latency()`, `status()`, `failed()`, `streamed()`, `for()`, `conversation()`, `feature()`, `invocation()`, `meta()`, `startedAt()`, `endedAt()`, `record()`.

### Prism

```php
$response = Prism::text()->using('openai', 'gpt-4o')->withPrompt('...')->generate();

AiModelUsageTracker::capturePrism($response, $user);
```

Enable it in config (`instrumentation.prism => true`).

### Raw HTTP clients

Set `instrumentation.http => true` to parse OpenAI/Anthropic-style `usage` blocks from outgoing HTTP responses automatically.

---

## Attribution

### Per-user (or any model)

Use `for()` on the manual builder, or set a global resolver so *every* recorded row is attributed automatically (great for auto-instrumentation):

```php
use AiModelUsageTracker\AiModelUsageTracker\Facades\AiModelUsageTracker;

// e.g. in a service provider or middleware
AiModelUsageTracker::resolveTrackableUsing(fn () => auth()->user());
```

### Per-conversation

When you use `laravel/ai` conversations, the conversation id is captured automatically on each recorded row (`conversation_id`). You can then aggregate usage for a single conversation:

```php
use AiModelUsageTracker\AiModelUsageTracker\Reporting\UsageReporter;

$summary = app(UsageReporter::class)->forConversation($conversationId);

$summary['totals'];   // ['records' => 5, 'total_tokens' => 8120, 'total_cost' => 0.0123, ...]
$summary['by_model']; // per-model breakdown for that conversation
```

For manual records, pass the id explicitly with `->conversation($conversationId)`.

---

## Per-request usage in your responses

Each request is recorded individually, so you can surface its cost inline:

```php
use AiModelUsageTracker\AiModelUsageTracker\Facades\AiModelUsageTracker;
use AiModelUsageTracker\AiModelUsageTracker\Http\Resources\UsageResource;

$record = AiModelUsageTracker::forInvocation($invocationId); // or ::latest()

return response()->json([
    'answer' => $answer,
    'usage' => $record ? (new UsageResource($record))->toArray($request) : null,
]);
```

`$record->toUsageArray()` returns a compact block of tokens, cost, currency, and latency.

---

## Cost accuracy

Costs are computed from `pricing.models` in config, then from a **fetched catalog** if you run `ai-usage:fetch-pricing`. Bundled rates cover common OpenAI, Anthropic, and Gemini models, expressed **per 1,000,000 tokens**. Dated model ids match the longest catalog prefix (`gpt-4o-2024-11-20` → `gpt-4o`). Provider prefixes like `google/gemini-3.1-flash-lite` are stripped.

> Requests for models **not** in config or the catalog are recorded with a **zero cost**, a `pricing_missing` flag in `metadata`, and a warning log so gaps are auditable.

Published config **always wins** over fetched rates (use it for negotiated prices).

### Auto-fetch (LiteLLM, then OpenRouter)

Fetch does **not** run during `record()`. Refresh the catalog on a schedule:

```bash
php artisan ai-usage:fetch-pricing
```

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('ai-usage:fetch-pricing')->daily();
```

Disable with `AI_USAGE_FETCH_PRICING=false`. `--force` bypasses the cache TTL (default 24 hours).

### Adding pricing for a model

If a model shows `$0` cost, either fetch the catalog, or add an override:

```php
'pricing' => [
    'currency' => 'USD',
    'models' => [
        'gemini' => [
            'gemini-3.1-flash-lite' => ['input' => 0.25, 'output' => 1.50, 'cache_read' => 0.025],
        ],
    ],
],
```

Then reprice historical rows:

```bash
php artisan ai-usage:reprice          # zero-cost / pricing_missing only
php artisan ai-usage:reprice --all
php artisan ai-usage:reprice --dry-run
```

Supported rate keys: `input`, `output`, `cache_write`, `cache_read`, `reasoning`, `per_image`, `per_second`. Missing `cache_write`/`cache_read` fall back to `input`; missing `reasoning` falls back to `output`.

---

## Reporting

```php
use AiModelUsageTracker\AiModelUsageTracker\Reporting\UsageReporter;

$reporter = app(UsageReporter::class);

$reporter->totals();                 // records, tokens, cost, failures
$reporter->forConversation($id);     // totals + per-model breakdown for one conversation
$reporter->byModel();                // grouped by model
$reporter->byProvider();             // grouped by provider
$reporter->byOperation();            // grouped by operation
$reporter->dailyTrend();             // cost/tokens per day
$reporter->topConsumers();           // biggest spenders (attributed models)
```

Every method except `forConversation()` accepts optional `$from`/`$to` `DateTimeInterface` bounds.

CLI summary:

```bash
php artisan ai-usage:report --days=30
```

---

## Budgets

Define spending caps in config; a `BudgetThresholdReached` event fires the moment a threshold is crossed:

```php
'budgets' => [
    'monthly' => ['period' => 'month', 'limit' => 500.0, 'thresholds' => [0.8, 1.0]],
],
```

Listen for the event to send alerts:

```php
use AiModelUsageTracker\AiModelUsageTracker\Events\BudgetThresholdReached;

Event::listen(BudgetThresholdReached::class, function (BudgetThresholdReached $event) {
    // notify your team
});
```

---

## Recording mode

Set `recording.mode` to `sync` (default, always exact) or `queue` to offload writes to a job for high-throughput apps:

```php
'recording' => [
    'mode' => 'queue',
    'queue' => ['connection' => null, 'queue' => null],
],
```

---

## Retention

```bash
php artisan ai-usage:prune --days=90
```

Set `retention_days` in config and schedule the command in `routes/console.php` (or `app/Console/Kernel.php`):

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('ai-usage:prune')->daily();
```

---

## Dashboard

`/ai-usage` is enabled by default. Define the gate:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewAiUsageDashboard', fn ($user) => $user->isAdmin());
```

### Laravel starter kits (Vue, React, Livewire)

If the app was created with an official Laravel starter kit, install the dashboard into that shell so it uses the same sidebar / header as `/dashboard`:

```bash
php artisan ai-usage:install
```

That command will:

1. Detect **vue**, **react**, or **livewire** from the kit’s layout files (or pass `--kit=`).
2. Publish `resources/js/pages/AiUsage/Dashboard.vue` or `.tsx` for Inertia kits.
3. Add an **AI Usage** item to the starter-kit sidebar (or header). Skip with `--no-nav`.

Then set the driver / layout to match the kit and rebuild assets for Inertia:

```env
AI_USAGE_DASHBOARD_DRIVER=inertia   # Vue or React kits
AI_USAGE_DASHBOARD_LAYOUT=starter-kit  # Livewire / Breeze Blade layout
```

```bash
npm run build
```

| Kit | Driver | What you get |
|-----|--------|----------------|
| Vue starter kit | `inertia` | Inertia page inside `AppLayout` + sidebar item |
| React starter kit | `inertia` | Same, with the published `.tsx` page |
| Livewire starter kit | `blade` + `layout=starter-kit` | Blade view inside `<x-layouts.app>` + Flux sidebar item |
| No kit | `blade` (default) | Standalone HTML at `/ai-usage` |

`dashboard.driver=auto` picks Inertia when a Vue/React kit is detected. `dashboard.layout=auto` uses the Livewire/Breeze app layout when that component exists, otherwise the standalone page.

Inertia kits also receive a shared `aiUsageNavigation` prop (`visible`, `url`, `label`, `icon`) so a custom sidebar can do:

```ts
const nav = page.props.aiUsageNavigation
if (nav?.visible) {
    items.push({ title: nav.label, href: nav.url, icon: Cpu })
}
```

Turn the shared item off with `dashboard.navigation.enabled` / `AI_USAGE_DASHBOARD_NAV=false`.

Publish pages without the install command:

```bash
php artisan vendor:publish --tag="ai-model-usage-tracker-inertia-vue"
php artisan vendor:publish --tag="ai-model-usage-tracker-inertia-react"
```

Adjust `dashboard.path` and `dashboard.middleware` as needed (`auth` is recommended for starter-kit apps).

---

## Testing

```bash
composer test
```

This runs static analysis (PHPStan/Larastan), code style (Pint), 100% type coverage, and the Pest test suite.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

When cutting a GitHub Release, paste **that version’s notes only** — not the whole changelog file (the updater action would nest Unreleased into the previous release).

## License

AI Model Usage Tracker is open-sourced software licensed under the [MIT license](LICENSE.md).
