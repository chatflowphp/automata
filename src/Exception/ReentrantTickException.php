<?php

declare(strict_types=1);

namespace Automata\Exception;

/**
 * Thrown when tick() is called while another operation on the same machine is still running.
 */
final class ReentrantTickException extends IllegalStateException
{
    public static function create(): self
    {
        return new self(
            'tick() was called while another operation is in progress. '
            . 'Listeners run after the operation commits; use the returned TickResult instead of '
            . 'ticking from inside a state, middleware, or transition guard.',
        );
    }
}
