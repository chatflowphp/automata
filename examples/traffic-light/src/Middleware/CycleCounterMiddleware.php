<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\Middleware;

use Automata\Contracts\CycleMiddlewareInterface;
use Automata\Core\CycleRequest;
use Automata\Core\CycleResponse;

final class CycleCounterMiddleware implements CycleMiddlewareInterface
{
    public function handle(CycleRequest $request, callable $next): CycleResponse
    {
        $context = $request->getContext();
        $ticks = $context->get('total_ticks');
        if (!is_int($ticks)) {
            $ticks = 0;
        }

        $context->set('total_ticks', $ticks + 1);

        return $next($request);
    }
}
