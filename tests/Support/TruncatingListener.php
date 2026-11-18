<?php

declare(strict_types=1);

namespace CraftAiObserver\Tests\Support;

use CraftAiObserver\Model\Trace;

/**
 * Stands in for a plugin that trims and redacts before it records.
 */
final class TruncatingListener extends RecordingListener
{
    protected function prepare(Trace $trace): ?Trace
    {
        return $trace->limit(20);
    }
}
