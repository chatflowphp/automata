<?php

declare(strict_types=1);

namespace Automata\Contracts;

/**
 * Marker interface designating a message as a command.
 *
 * Commands instruct automata or external systems to perform an action and are generally processed once.
 */
interface CommandInterface extends MessageInterface
{
}
