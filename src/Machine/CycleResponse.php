<?php

declare(strict_types=1);

namespace Automata\Machine;

use Automata\Exception\InvalidCycleResponseException;
use Automata\Messaging\CommandInterface;
use Automata\Messaging\EventInterface;
use Automata\Messaging\TransitionCommand;

/**
 * Immutable result of process() or onEnter(): commands to dispatch and events to emit, in order.
 */
final class CycleResponse
{
    /**
     * @var list<CommandInterface>
     */
    private readonly array $commands;

    /**
     * @var list<EventInterface>
     */
    private readonly array $events;

    /**
     * @param array<array-key, mixed> $commands
     * @param array<array-key, mixed> $events
     */
    public function __construct(array $commands = [], array $events = [])
    {
        $this->commands = self::ensureAll($commands, CommandInterface::class);
        $this->events = self::ensureAll($events, EventInterface::class);
    }

    public static function none(): self
    {
        return new self();
    }

    public static function fromCommand(CommandInterface $command): self
    {
        return new self([$command]);
    }

    /**
     * @param array<array-key, CommandInterface> $commands
     */
    public static function fromCommands(array $commands): self
    {
        return new self($commands);
    }

    public static function fromEvent(EventInterface $event): self
    {
        return new self([], [$event]);
    }

    /**
     * @param array<array-key, EventInterface> $events
     */
    public static function fromEvents(array $events): self
    {
        return new self([], $events);
    }

    public static function transitionTo(string $stateId): self
    {
        return self::fromCommand(new TransitionCommand($stateId));
    }

    public function withCommand(CommandInterface $command): self
    {
        return new self([...$this->commands, $command], $this->events);
    }

    /**
     * @param array<array-key, CommandInterface> $commands
     */
    public function withCommands(array $commands): self
    {
        return new self([...$this->commands, ...array_values($commands)], $this->events);
    }

    public function withEvent(EventInterface $event): self
    {
        return new self($this->commands, [...$this->events, $event]);
    }

    /**
     * @param array<array-key, EventInterface> $events
     */
    public function withEvents(array $events): self
    {
        return new self($this->commands, [...$this->events, ...array_values($events)]);
    }

    public function withTransitionTo(string $stateId): self
    {
        return $this->withCommand(new TransitionCommand($stateId));
    }

    public function merge(self $other): self
    {
        return new self(
            [...$this->commands, ...$other->commands],
            [...$this->events, ...$other->events],
        );
    }

    public function isEmpty(): bool
    {
        return $this->commands === [] && $this->events === [];
    }

    /**
     * @return list<CommandInterface>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @return list<EventInterface>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @template T of object
     *
     * @param array<array-key, mixed> $values
     * @param class-string<T> $interface
     *
     * @return list<T>
     */
    private static function ensureAll(array $values, string $interface): array
    {
        $checked = [];

        foreach ($values as $value) {
            if (!$value instanceof $interface) {
                throw InvalidCycleResponseException::expected($interface, $value);
            }

            $checked[] = $value;
        }

        return $checked;
    }
}
