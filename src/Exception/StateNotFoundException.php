<?php

declare(strict_types=1);

namespace Automata\Exception;

/**
 * Thrown when an operation refers to a state id that is not registered.
 */
final class StateNotFoundException extends AutomataException
{
    public static function forId(string $stateId): self
    {
        return new self(\sprintf('State "%s" is not registered.', $stateId));
    }
}
