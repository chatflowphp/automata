<?php

declare(strict_types=1);

namespace AutomataExamples\SimpleWorkflow\Middleware;

use Automata\Contracts\CycleMiddlewareInterface;
use Automata\Core\CycleRequest;
use Automata\Core\CycleResponse;

final class CycleCounterMiddleware implements CycleMiddlewareInterface
{
    public function handle(CycleRequest $request, callable $next): CycleResponse
    {
        $context = $request->getContext();
        $count = $context->get('cycle_count', 0);
        if (!is_int($count)) {
            $count = 0;
        }

        $context->set('cycle_count', $count + 1);

        return $next($request);
    }
}
