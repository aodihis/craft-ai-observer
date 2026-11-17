<?php

declare(strict_types=1);

namespace CraftAiObserver\Dev\Fixtures\Violations;

/**
 * Fixture: EXPECTED violations of `craft/comment-placement`.
 */
final class NoisyComments
{
    // fixed the return value here
    private const int LIMIT = 5;

    public function run(int $value): int
    {
        // clamp the value
        if ($value > self::LIMIT) {
            return self::LIMIT; // never above the limit
        }

        return $value;
    }
}
