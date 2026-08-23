# Release Notes

## [Unreleased](https://github.com/sanjayacloud/ai-model-usage-tracker/compare/v1.2.0...HEAD)

### Added

- Starter-kit dashboard install: `php artisan ai-usage:install` detects Vue, React, or Livewire kits, publishes the matching page, and adds a sidebar / header link.
- `dashboard.driver=auto`, `dashboard.layout` (`auto` / `starter-kit` / `standalone`), and `dashboard.kit`.
- Inertia shared `aiUsageNavigation` prop for custom sidebars.
- Livewire `<x-layouts.app>` and Breeze `<x-app-layout>` Blade wrappers.
- React `Dashboard.tsx` page; Vue page restyled for the official starter-kit tokens.

## [v1.2.0](https://github.com/sanjayacloud/ai-model-usage-tracker/compare/v1.1.2...v1.2.0) - 2026-08-23

### Added

- Starter-kit dashboard install: `php artisan ai-usage:install` detects Vue, React, or Livewire kits, publishes the matching page, and adds a sidebar / header link.
- `dashboard.driver=auto`, `dashboard.layout` (`auto` / `starter-kit` / `standalone`), and `dashboard.kit`.
- Inertia shared `aiUsageNavigation` prop for custom sidebars.
- Livewire `<x-layouts.app>` and Breeze `<x-app-layout>` Blade wrappers.
- React `Dashboard.tsx` page; Vue page restyled for the official starter-kit tokens.

## [v1.1.2](https://github.com/sanjayacloud/ai-model-usage-tracker/releases/tag/v1.1.2) - 2026-08-19

### Fixed

- Recording fails open: a database error while persisting usage is reported and returns null instead of taking down the host request.

## [v1.1.1](https://github.com/sanjayacloud/ai-model-usage-tracker/releases/tag/v1.1.1) - 2026-08-19

### Fixed

- Auto-loaded migrations no-op when the table or columns already exist, so apps that published 1.0 migrations can upgrade without a duplicate-table error.

## [v1.1.0](https://github.com/sanjayacloud/ai-model-usage-tracker/releases/tag/v1.1.0) - 2026-08-19

### Added

- Prefix / alias pricing lookup (dated model ids, `provider/model` slashes, longest-prefix match).
- Warning log when a model has no rates (`pricing_missing` is unchanged).
- Package migrations load automatically via `loadMigrationsFrom()`.
- Auto-fetched pricing catalog: LiteLLM first, OpenRouter fallback (`ai-usage:fetch-pricing`). HTTP never runs during `record()`.
- `ai-usage:reprice` to recompute historical zero-cost / `pricing_missing` rows.
- Blade dashboard as the default (`dashboard.driver=blade`); Inertia remains optional.
- `feature_key` column and `PendingUsage::feature()`.
- `UsageRecord::factory()`.
- `per_image` and `per_second` pricing keys.
- Dual `laravel/ai` token field names (`promptTokens` / `inputTokens`).
- `UsageTracker` facade alias.

### Changed

- Flattened changelog (removed nested Unreleased notes inside v1.0.0).
- GitHub Release bodies must be that version’s notes only.

## [v1.0.0](https://github.com/sanjayacloud/ai-model-usage-tracker/releases/tag/v1.0.0) - 2026-08-14

First public release.

### Added

- Per-conversation attribution: `conversation_id` is stored on each usage row.
- Automatic capture of `laravel/ai` conversation ids from `AgentPrompted`.
- Fluent `PendingUsage::conversation()`.
- `UsageRecord::scopeForConversation()` and `UsageReporter::forConversation()`.
- Bundled Gemini rates for `gemini-3.1-flash-lite` and `gemini-2.5-flash-lite`.
- Step-by-step README.

### Changed

- Unknown models record at `$0` with `metadata.pricing_missing = true`.
