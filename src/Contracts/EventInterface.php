<?php

declare(strict_types=1);

namespace Automata\Contracts;

/**
 * Marker interface designating a message as an event.
 *
 * Events signal that something already happened and may be consumed by multiple listeners.
 */
interface EventInterface extends MessageInterface
{
}
