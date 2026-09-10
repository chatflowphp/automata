<?php

declare(strict_types=1);

namespace Automata\Machine\Transition;

use Automata\Context\ContextInterface;

/**
 * Decides whether a transition between two distinct states is allowed.
 *
 * The policy is not consulted for self-transitions, which are always a no-op.
 */
interface TransitionPolicyInterface
{
    public function isAllowed(string $fromStateId, string $toStateId, ContextInterface $context): bool;
}
