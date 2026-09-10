<?php

declare(strict_types=1);

namespace Automata\Messaging;

/**
 * A command that asks the state machine to switch to another state.
 *
 * The machine applies the transition first and then dispatches the command itself.
 */
interface TransitionCommandInterface extends CommandInterface
{
    public function getTargetStateId(): string;
}
