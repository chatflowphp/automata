<?php

declare(strict_types=1);

namespace Automata\Contracts;

use Automata\Core\CycleRequest;
use Automata\Core\CycleResponse;

/**
 * Middleware executed on each orchestrator cycle.
 *
 * Implementations can mutate context, short-circuit command dispatch, or augment results
 * before handing control to the next middleware/automaton in the pipeline.
 */
interface CycleMiddlewareInterface
{
    /**
     * @param CycleRequest $request Aggregates the current input and shared context.
     * @param callable(CycleRequest):CycleResponse $next Next handler in the pipeline.
     *
     * @return CycleResponse Command/event payload returned by the pipeline.
     */
    public function handle(CycleRequest $request, callable $next): CycleResponse;
}
