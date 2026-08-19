---
name: ai-model-usage-tracker-development
description: >
  Configure and apply the Ai Model Usage Tracker package in Laravel applications.
license: MIT
metadata:
  author: Sanjaya
---

# Ai Model Usage Tracker

Use this skill when a Laravel application needs to install, configure, or call `sanjayacloud/ai-model-usage-tracker`.

## Primary Goal

Apply the package's public API in the smallest correct way: install, migrate, optionally publish config, capture usage, then report or reprice.

## Workflow

### 1. Inspect the Laravel app

- Confirm Laravel 12 or 13 and PHP 8.3+.
- Check whether `laravel/ai` is already installed (automatic capture) or the app will record manually.
- Do not copy App Pro / host-app chat code into this package, or the reverse.

### 2. Install

```bash
composer require sanjayacloud/ai-model-usage-tracker
php artisan migrate
```

Migrations load automatically. Publishing them is optional. Never `vendor:publish --tag=ai-model-usage-tracker-migrations --force` later — that duplicates the create-table migration.

Publish config when the app needs custom rates, budgets, or dashboard settings:

```bash
php artisan vendor:publish --tag="ai-model-usage-tracker-config"
```

The `AiModelUsageTracker` facade is auto-discovered. `UsageTracker` is a shorter alias.

### 3. Capture usage

**laravel/ai (default):** leave `instrumentation.laravel-ai` true. The next agent, embedding, image, audio, or transcription call is recorded. Conversation ids are stored when the SDK call is inside a conversation. Token fields accept both `promptTokens` / `completionTokens` and `inputTokens` / `outputTokens`.

**Manual:**

```php
use AiModelUsageTracker\AiModelUsageTracker\Facades\AiModelUsageTracker;
use AiModelUsageTracker\AiModelUsageTracker\Enums\Operation;

AiModelUsageTracker::for($user)
    ->provider('openai')
    ->model('gpt-4o')
    ->operation(Operation::Chat)
    ->tokens(prompt: 1200, completion: 350)
    ->conversation($conversationId)
    ->feature('support-bot')
    ->record();
```

**Prism:** `AiModelUsageTracker::capturePrism($response, $user)` and set `instrumentation.prism` true.

**Attribution for auto-capture:** `AiModelUsageTracker::resolveTrackableUsing(fn () => auth()->user());`

### 4. Pricing (never HTTP on `record()`)

Resolution order: published `pricing.models` → cached catalog → bundled defaults → longest-prefix match (`gpt-4o-2024-11-20` → `gpt-4o`). Unknown models record `$0` with `metadata.pricing_missing` and a warning log.

Fetch catalogs on a schedule, not in the request:

```php
Schedule::command('ai-usage:fetch-pricing')->daily();
```

Config overrides always win over fetched LiteLLM / OpenRouter rates. After adding rates, backfill history:

```bash
php artisan ai-usage:reprice          # zero-cost / pricing_missing only
php artisan ai-usage:reprice --dry-run
```

Rate keys: `input`, `output`, `cache_write`, `cache_read`, `reasoning`, `per_image`, `per_second` (per 1M tokens except the per-unit keys).

### 5. Report and dashboard

```php
app(\AiModelUsageTracker\AiModelUsageTracker\Reporting\UsageReporter::class)
    ->forConversation($conversationId);
```

Also: `totals()`, `byModel()`, `byProvider()`, `byOperation()`, `dailyTrend()`, `topConsumers()`. CLI: `php artisan ai-usage:report --days=30`.

Blade dashboard is the default at `/ai-usage`. Define `Gate::define('viewAiUsageDashboard', ...)`. Set `dashboard.driver` to `inertia` only when Inertia + Vue are installed and the Vue page is published.

Tests: `UsageRecord::factory()->create()`.

## Rules

- Do not HTTP inside recording. Fetch is `ai-usage:fetch-pricing` only.
- Do not `--force` republish package migrations.
- GitHub Release bodies must be **that version’s notes only**, not the whole `CHANGELOG.md`.
- Fail-open: missing rates record zero cost; fetch failures use cache then bundled config.

## Examples

- Install in an app that already uses `laravel/ai`: require, migrate, schedule `ai-usage:fetch-pricing`, define the dashboard gate.
- A product-page assistant shows `$0` for `gemini-3.1-flash-lite-preview`: prefix match or fetch catalog, then `ai-usage:reprice`.
- Group spend by feature: `->feature('support-bot')` on manual records.

## Anti-patterns

- Do not document package internals here.
- Do not fetch pricing during a web request or inside `record()`.
- Do not treat Inertia as required — Blade is the default dashboard.
