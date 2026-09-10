<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\Middleware;

use Automata\Machine\CycleRequest;
use Automata\Machine\TickResult;
use Automata\Middleware\TickMiddlewareInterface;

final class CycleCounterMiddleware implements TickMiddlewareInterface
{
    public function handle(CycleRequest $request, callable $next): TickResult
    {
        $request->getContext()->increment('total_ticks');

        return $next($request);
    }
}
