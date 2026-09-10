<?php

declare(strict_types=1);

namespace Automata\Exception;

/**
 * Thrown by typed context accessors when the stored value has a different type.
 */
final class ContextTypeException extends AutomataException
{
    public static function forKey(string $key, string $expected, mixed $actual): self
    {
        return new self(\sprintf(
            'Context key "%s" is expected to hold %s, got %s.',
            $key,
            $expected,
            get_debug_type($actual),
        ));
    }
}
