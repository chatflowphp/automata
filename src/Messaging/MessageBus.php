<?php

declare(strict_types=1);

namespace Automata\Messaging;

/**
 * Default message bus. Handlers run synchronously in subscription order:
 * exact class first, then parent classes, then interfaces.
 */
final class MessageBus implements MessageBusInterface
{
    /**
     * @var array<class-string, list<callable>>
     */
    private array $handlers = [];

    public function dispatch(object $message): void
    {
        foreach ($this->typesOf($message) as $type) {
            foreach ($this->handlers[$type] ?? [] as $handler) {
                $handler($message);
            }
        }
    }

    public function subscribe(string $messageClass, callable $handler): void
    {
        $this->handlers[$messageClass][] = $handler;
    }

    /**
     * @return list<class-string>
     */
    private function typesOf(object $message): array
    {
        return [
            $message::class,
            ...array_values(class_parents($message)),
            ...array_values(class_implements($message)),
        ];
    }
}
