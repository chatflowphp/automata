<?php

declare(strict_types=1);

namespace Automata\Messages;

use Automata\Contracts\TransitionCommandInterface;

/**
 * Command emitted by automata to request a transition to another FSM state.
 */
final class TransitionCommand extends AbstractCommand implements TransitionCommandInterface
{
    public const NAME = self::class;

    public function __construct(private readonly string $nextAutomatonId)
    {
    }

    public function getNextAutomatonId(): string
    {
        return $this->nextAutomatonId;
    }

    public function getPayload(): mixed
    {
        return $this->nextAutomatonId;
    }
}
