<?php

declare(strict_types=1);

namespace Automata\Events;

use Automata\Messaging\EventInterface;

/**
 * Emitted when a state becomes current through start() or a transition. Not emitted on restore().
 */
final class StateActivated implements EventInterface
{
    public function __construct(
        public readonly string $stateId,
        public readonly ?string $previousStateId,
    ) {}
}
