<?php

declare(strict_types=1);

namespace Automata\Exception;

/**
 * Thrown when a CycleResponse is built from values that are not commands or events.
 */
final class InvalidCycleResponseException extends AutomataException
{
    public static function expected(string $interface, mixed $actual): self
    {
        return new self(\sprintf('CycleResponse expects instances of %s, got %s.', $interface, get_debug_type($actual)));
    }
}
