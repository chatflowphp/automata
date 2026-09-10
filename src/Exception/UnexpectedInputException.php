<?php

declare(strict_types=1);

namespace Automata\Exception;

use Automata\Machine\InputInterface;
use Automata\State\StateInterface;

/**
 * Thrown by AbstractState when the input does not match the declared INPUT class.
 */
final class UnexpectedInputException extends AutomataException
{
    public static function create(StateInterface $state, string $expected, InputInterface $actual): self
    {
        return new self(\sprintf(
            'State "%s" expects input of type %s, got %s.',
            $state->getId(),
            $expected,
            $actual::class,
        ));
    }
}
