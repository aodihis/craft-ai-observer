<?php

declare(strict_types=1);

namespace CraftAiObserver\Tree;

use CraftAiObserver\Model\Trace;
use DateTimeImmutable;

/**
 * The traces that are still open, and the guard that closes the stalled ones.
 *
 * A trace is stalled when no event has touched it for `guardSeconds`; that is
 * what keeps a legitimately long run alive while still catching events that
 * were lost (docs/rules/logic.md).
 *
 * @internal
 */
final class OpenTraces
{
    /**
     * @var array<string, PendingTrace>
     */
    private array $traces = [];

    public function add(string $id, PendingTrace $trace): void
    {
        $this->traces[$id] = $trace;
    }

    public function find(string $id): ?PendingTrace
    {
        return $this->traces[$id] ?? null;
    }

    public function remove(string $id): ?PendingTrace
    {
        $trace = $this->find($id);
        unset($this->traces[$id]);

        return $trace;
    }

    /**
     * Remove every stalled trace and return it closed and marked incomplete.
     *
     * @return list<Trace>
     */
    public function expired(DateTimeImmutable $now, ?int $guardSeconds): array
    {
        if ($guardSeconds === null) {
            return [];
        }

        $expired = [];

        foreach ($this->traces as $id => $trace) {
            if (!$trace->isStalled($now, $guardSeconds)) {
                continue;
            }

            unset($this->traces[$id]);
            $expired[] = $trace->expire($now);
        }

        return $expired;
    }
}
