<?php

declare(strict_types=1);

use AiModelUsageTracker\AiModelUsageTracker\Tests\TestCase;
use Illuminate\Foundation\Auth\User;

uses(TestCase::class)->in(__DIR__);

function dashboardUser(): User
{
    return new class extends User
    {
        protected $table = 'users';

        public $exists = true;

        protected $attributes = ['id' => 1];
    };
}
