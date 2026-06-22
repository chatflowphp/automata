<?php

declare(strict_types=1);

namespace Automata\Contracts;

/**
 * Defines the protocol for dispatching messages and registering handlers within the system.
 *
 * The orchestrator collaborates with a message bus to deliver commands and events between automata
 * and any external integrations interested in reacting to state changes.
 */
interface MessageBusInterface
{
    /**
     * Dispatches a message to the bus implementation.
     *
     * Implementations may enqueue the message, forward it synchronously, or apply other transports,
     * but they must guarantee that subscribed handlers eventually receive the message.
     *
     * @param MessageInterface $message Message instance to be routed.
     *
     * @return void
     */
    public function dispatch(MessageInterface $message): void;

    /**
     * Registers a handler for messages with a particular logical name.
     *
     * @param string $messageName Name returned by MessageInterface::getName().
     * @param callable(MessageInterface):void $handler Callback to execute for matching messages.
     *
     * @return void
     */
    public function subscribe(string $messageName, callable $handler): void;
}
