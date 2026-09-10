<?php

declare(strict_types=1);

namespace Automata\Middleware;

use Automata\Machine\CycleRequest;
use Automata\Machine\TickResult;

/**
 * Wraps a whole tick: process(), transitions, and the collection of emitted messages.
 *
 * Messages reach the bus only after the outermost middleware returns, so a middleware can wrap the
 * tick in a database transaction, persist a snapshot from the result, or veto by throwing, which
 * rolls the machine back to its state before the tick.
 */
interface TickMiddlewareInterface
{
    /**
     * @param callable(CycleRequest): TickResult $next
     */
    public function handle(CycleRequest $request, callable $next): TickResult;
}
