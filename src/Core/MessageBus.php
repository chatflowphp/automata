<?php

declare(strict_types=1);

namespace Automata\Core;

use Automata\Contracts\MessageBusInterface;
use Automata\Contracts\MessageInterface;

/**
 * Default in-memory message bus implementation used by the core orchestrator.
 *
 * Stores handlers per message name and synchronously invokes them during dispatch.
 */
final class MessageBus implements MessageBusInterface
{
    /**
     * @var array<string, list<callable>>
     */
    private array $handlers = [];

    /**
     * {@inheritdoc}
     */
    public function dispatch(MessageInterface $message): void
    {
        $messageName = $message->getName();

        foreach ($this->handlers[$messageName] ?? [] as $handler) {
            $handler($message);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(string $messageName, callable $handler): void
    {
        $this->handlers[$messageName][] = $handler;
    }
}
