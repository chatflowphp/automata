<?php

declare(strict_types=1);

namespace Automata\Contracts;

/**
 * Defines the minimal contract for any message exchanged over the message bus.
 * Implementations act as thin carriers for typed payloads originating from automata or user code.
 */
interface MessageInterface
{
    /**
     * Gets the logical name (or type) of the message for routing purposes.
     *
     * The name is typically a short, descriptive identifier such as "PlaceBet" or "OrderCreated"
     * and is used by the message bus to route the message to the appropriate handlers.
     *
     * @return string Non-empty message name unique within the messaging domain.
     */
    public function getName(): string;

    /**
     * Retrieves the payload carried by the message.
     *
     * The payload should be a serializable data structure (DTO or associative array) that contains
     * all the information required by downstream handlers to process the message.
     *
     * @return mixed Application-specific data associated with the message.
     */
    public function getPayload(): mixed;
}
