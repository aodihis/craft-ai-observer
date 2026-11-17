<?php

declare(strict_types=1);

namespace CraftAiObserver\Dev\Fixtures\Violations;

use Langfuse\Client;

/**
 * Fixture: EXPECTED violations of `craft/forbidden-imports`
 * (the `use` statement and the resolved type hint).
 */
final class ImportsObserverSdk
{
    public function export(Client $client): void
    {
        $client->trace();
    }
}
