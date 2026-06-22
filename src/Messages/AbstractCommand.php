<?php

declare(strict_types=1);

namespace Automata\Messages;

use Automata\Contracts\CommandInterface;

/**
 * Convenience base class for commands dispatched by the orchestrator.
 *
 * Extending classes define a unique NAME constant and can optionally override {@see getPayload()}
 * for custom serialization.
 */
abstract class AbstractCommand implements CommandInterface
{
    public const NAME = '';

    public function getName(): string
    {
        /** @var string $name */
        $name = static::NAME;

        return $name;
    }

    public function getPayload(): mixed
    {
        return null;
    }

}
