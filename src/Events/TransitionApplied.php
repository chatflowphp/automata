<?php

declare(strict_types=1);

namespace Automata\Events;

use Automata\Messaging\EventInterface;

/**
 * Emitted after the current state changed from one state to another.
 */
final class TransitionApplied implements EventInterface
{
    public function __construct(
        public readonly string $fromStateId,
        public readonly string $toStateId,
    ) {}
}
