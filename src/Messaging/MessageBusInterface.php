<?php

declare(strict_types=1);

namespace Automata\Messaging;

/**
 * Synchronous in-process message bus routed by message class.
 *
 * A handler subscribed to a class receives every message that is an instance of that class,
 * including subclasses and implementations, so subscribing to EventInterface::class observes all events.
 */
interface MessageBusInterface
{
    public function dispatch(object $message): void;

    /**
     * @template T of object
     *
     * @param class-string<T> $messageClass
     * @param callable(T): void $handler
     */
    public function subscribe(string $messageClass, callable $handler): void;
}
