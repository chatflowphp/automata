<?php

declare(strict_types=1);

namespace Automata\Messages;

/**
 * Command representing an intentionally empty action.
 *
 * Emitted by automata when no side effects are required for a particular cycle.
 */
final class NoopCommand extends AbstractCommand
{
    public const NAME = self::class;
}
