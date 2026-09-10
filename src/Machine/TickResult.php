<?php

declare(strict_types=1);

namespace Automata\Machine;

/**
 * Outcome of one tick: which state handled it, where the machine ended up, and every message emitted.
 */
final class TickResult
{
    /**
     * @param list<object> $messages Every command and event emitted during the tick, in dispatch order.
     */
    public function __construct(
        public readonly int $tickNumber,
        public readonly string $fromStateId,
        public readonly string $toStateId,
        public readonly CycleResponse $response,
        public readonly array $messages,
    ) {}

    public function transitioned(): bool
    {
        return $this->fromStateId !== $this->toStateId;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    public function messagesOf(string $class): array
    {
        $matching = [];

        foreach ($this->messages as $message) {
            if ($message instanceof $class) {
                $matching[] = $message;
            }
        }

        return $matching;
    }
}
