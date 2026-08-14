# Release Notes

## [Unreleased](https://github.com/sanjayacloud/ai-model-usage-tracker/compare/v0.1.0...1.x)

### Added

- Per-conversation attribution: `conversation_id` is stored on each usage row.
- Automatic capture of `laravel/ai` conversation ids from `AgentPrompted` (`$response->conversationId`).
- Fluent `PendingUsage::conversation()` for manual records.
- `UsageRecord::scopeForConversation()` and `UsageReporter::forConversation()` (totals plus a per-model breakdown).
- Bundled Gemini rates (per 1M tokens): `gemini-3.1-flash-lite` (`input` 0.25, `output` 1.50, `cache_read` 0.025) and `gemini-2.5-flash-lite` (`input` 0.10, `output` 0.40, `cache_read` 0.01).
- Step-by-step README covering install, capture, attribution, pricing, reporting, budgets, retention, and the dashboard.

### Changed

- Unknown models continue to record at `$0` with `metadata.pricing_missing = true`. Add rates in the published config and run `php artisan config:clear` so new records are priced; existing zero-cost rows are not backfilled.

## [v0.1.0](https://github.com/sanjayacloud/ai-model-usage-tracker/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
