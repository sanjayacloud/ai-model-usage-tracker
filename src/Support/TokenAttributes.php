<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Support;

class TokenAttributes
{
    public static function int(object $source, string ...$names): int
    {
        foreach ($names as $name) {
            if (isset($source->{$name})) {
                return (int) $source->{$name};
            }
        }

        return 0;
    }
}
