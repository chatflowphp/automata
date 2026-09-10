<?php

declare(strict_types=1);

namespace Automata\Events;

use Automata\Machine\CycleRequest;
use Automata\Machine\TickResult;
use Automata\Messaging\EventInterface;

/**
 * Emitted last for every tick, after all other messages of that tick were dispatched.
 */
final class TickCompleted implements EventInterface
{
    public function __construct(
        public readonly CycleRequest $request,
        public readonly TickResult $result,
    ) {}
}
