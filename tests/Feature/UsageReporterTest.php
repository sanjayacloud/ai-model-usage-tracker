<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTracker;
use AiModelUsageTracker\AiModelUsageTracker\Enums\UsageStatus;
use AiModelUsageTracker\AiModelUsageTracker\Reporting\UsageReporter;

beforeEach(function () {
    $tracker = app(AiModelUsageTracker::class);
    $tracker->track()->provider('openai')->model('gpt-4o')->tokens(prompt: 1000, completion: 1000)->record();
    $tracker->track()->provider('openai')->model('gpt-4o-mini')->tokens(prompt: 500, completion: 500)->record();
    $tracker->track()->provider('anthropic')->model('claude-3-5-haiku-latest')->tokens(prompt: 200)->status(UsageStatus::Failed)->record();
});

it('aggregates totals across records', function () {
    $totals = app(UsageReporter::class)->totals();

    expect($totals['records'])->toBe(3)
        ->and($totals['total_tokens'])->toBe(3200)
        ->and($totals['failures'])->toBe(1)
        ->and($totals['total_cost'])->toBeGreaterThan(0.0);
});

it('groups usage by model', function () {
    $byModel = app(UsageReporter::class)->byModel();

    expect($byModel)->toHaveCount(3)
        ->and($byModel->pluck('model')->all())->toContain('gpt-4o', 'gpt-4o-mini', 'claude-3-5-haiku-latest');
});

it('summarizes usage for a single conversation', function () {
    $tracker = app(AiModelUsageTracker::class);
    $tracker->track()->provider('openai')->model('gpt-4o')->tokens(prompt: 300, completion: 100)->conversation('conv-abc')->record();
    $tracker->track()->provider('openai')->model('gpt-4o')->tokens(prompt: 200, completion: 100)->conversation('conv-abc')->record();
    $tracker->track()->provider('openai')->model('gpt-4o')->tokens(prompt: 999)->conversation('conv-other')->record();

    $summary = app(UsageReporter::class)->forConversation('conv-abc');

    expect($summary['totals']['records'])->toBe(2)
        ->and($summary['totals']['total_tokens'])->toBe(700)
        ->and($summary['by_model'])->toHaveCount(1)
        ->and($summary['by_model']->first()['model'])->toBe('gpt-4o');
});

it('groups usage by provider', function () {
    $byProvider = app(UsageReporter::class)->byProvider();

    $openai = $byProvider->firstWhere('provider', 'openai');

    expect($byProvider)->toHaveCount(2)
        ->and($openai['records'])->toBe(2);
});
