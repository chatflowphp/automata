<?php

declare(strict_types=1);

namespace Automata\Contracts;

/**
 * Marker for commands that instruct the orchestrator to transition to another automaton.
 */
interface TransitionCommandInterface
{
    /**
     * Identifier of the automaton that should become active.
     *
     * @return string
     */
    public function getNextAutomatonId(): string;
}
