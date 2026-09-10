<?php

declare(strict_types=1);

namespace Automata\Exception;

/**
 * Thrown when a state is registered under an id that is already taken.
 */
final class DuplicateStateException extends AutomataException
{
    public static function forId(string $stateId): self
    {
        return new self(\sprintf('State "%s" is already registered. Use replace() to override it.', $stateId));
    }
}
