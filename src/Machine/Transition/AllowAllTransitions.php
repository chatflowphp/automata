<?php

declare(strict_types=1);

namespace Automata\Machine\Transition;

use Automata\Context\ContextInterface;

/**
 * Default policy: any state may transition to any registered state.
 */
final class AllowAllTransitions implements TransitionPolicyInterface
{
    public function isAllowed(string $fromStateId, string $toStateId, ContextInterface $context): bool
    {
        return true;
    }
}
