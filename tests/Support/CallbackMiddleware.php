<?php

declare(strict_types=1);

namespace Automata\Tests\Support;

use Automata\Machine\CycleRequest;
use Automata\Machine\TickResult;
use Automata\Middleware\TickMiddlewareInterface;
use Closure;

final class CallbackMiddleware implements TickMiddlewareInterface
{
    /**
     * @param Closure(CycleRequest, callable(CycleRequest): TickResult): TickResult $callback
     */
    public function __construct(private readonly Closure $callback) {}

    public function handle(CycleRequest $request, callable $next): TickResult
    {
        return ($this->callback)($request, $next);
    }
}
