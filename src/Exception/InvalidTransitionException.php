<?php

declare(strict_types=1);

namespace Automata\Exception;

/**
 * Thrown when a transition is rejected by the transition policy or exceeds the chain limit.
 */
final class InvalidTransitionException extends AutomataException
{
    public static function notAllowed(string $fromStateId, string $toStateId): self
    {
        return new self(\sprintf('Transition from "%s" to "%s" is not allowed.', $fromStateId, $toStateId));
    }

    public static function chainTooLong(int $limit): self
    {
        return new self(\sprintf(
            'More than %d transitions were applied in a single operation. Check onEnter() for a transition loop.',
            $limit,
        ));
    }
}
