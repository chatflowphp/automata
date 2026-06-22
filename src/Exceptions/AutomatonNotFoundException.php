<?php

declare(strict_types=1);

namespace Automata\Exceptions;

/**
 * Thrown when the orchestrator is asked to operate on a non-registered automaton.
 */
final class AutomatonNotFoundException extends AutomataException
{
}
