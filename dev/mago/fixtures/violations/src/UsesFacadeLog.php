<?php

declare(strict_types=1);

namespace CraftAiObserver\Dev\Fixtures\Violations;

use Illuminate\Support\Facades\Log;

/**
 * Fixture: EXPECTED violations of `craft/no-facade-log` (the `use` statement
 * and the `Log::info()` call site).
 */
final class UsesFacadeLog
{
    public function record(): void
    {
        Log::info('trace recorded');
    }
}
