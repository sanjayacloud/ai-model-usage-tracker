<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Enums;

enum Driver: string
{
    case Manual = 'manual';
    case LaravelAi = 'laravel-ai';
    case Prism = 'prism';
    case Http = 'http';
}
