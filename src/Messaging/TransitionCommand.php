<?php

declare(strict_types=1);

namespace Automata\Messaging;

/**
 * Built-in transition command. Use CycleResponse::transitionTo() to create one.
 */
final class TransitionCommand implements TransitionCommandInterface
{
    public function __construct(public readonly string $targetStateId) {}

    public function getTargetStateId(): string
    {
        return $this->targetStateId;
    }
}
