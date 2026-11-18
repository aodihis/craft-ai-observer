<?php

declare(strict_types=1);

namespace CraftAiObserver\Tests\Support;

use DateTimeImmutable;

use function sprintf;

/**
 * A clock the tests can move forward.
 */
final class MutableClock
{
    public function __construct(
        private DateTimeImmutable $now
    ) {
    }

    public function __invoke(): DateTimeImmutable
    {
        return $this->now;
    }

    public function tick(int $seconds): void
    {
        $this->now = $this->now->modify(sprintf('+%d seconds', $seconds));
    }
}
