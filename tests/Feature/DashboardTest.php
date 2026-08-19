<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\AiModelUsageTracker;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia;

function dashboardUser(): User
{
    return new class extends User
    {
        protected $table = 'users';

        public $exists = true;

        protected $attributes = ['id' => 1];
    };
}

it('forbids access when the gate denies', function () {
    Gate::define('viewAiUsageDashboard', fn (?User $user = null) => false);

    $this->actingAs(dashboardUser())
        ->get(route('ai-usage.dashboard'))
        ->assertForbidden();
});

it('renders the dashboard when the gate allows', function () {
    Gate::define('viewAiUsageDashboard', fn (?User $user = null) => true);

    app(AiModelUsageTracker::class)->track()->provider('openai')->model('gpt-4o')->tokens(prompt: 1000, completion: 500)->record();

    $this->actingAs(dashboardUser())
        ->get(route('ai-usage.dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('AiUsage/Dashboard')
            ->has('totals')
            ->has('byModel')
            ->where('totals.records', 1)
        );
});

it('renders the blade dashboard when the driver is blade', function () {
    config()->set('ai-model-usage-tracker.dashboard.driver', 'blade');
    Gate::define('viewAiUsageDashboard', fn (?User $user = null) => true);

    app(AiModelUsageTracker::class)->track()->provider('openai')->model('gpt-4o')->tokens(prompt: 10)->record();

    $this->actingAs(dashboardUser())
        ->get(route('ai-usage.dashboard'))
        ->assertOk()
        ->assertSee('AI usage', false)
        ->assertSee('By model', false);
});

it('returns summary json from the api endpoint', function () {
    Gate::define('viewAiUsageDashboard', fn (?User $user = null) => true);

    app(AiModelUsageTracker::class)->track()->provider('openai')->model('gpt-4o')->tokens(prompt: 10)->record();

    $this->actingAs(dashboardUser())
        ->getJson(route('ai-usage.api.summary'))
        ->assertOk()
        ->assertJsonStructure(['totals', 'daily_trend', 'by_model', 'by_provider', 'top_consumers']);
});
