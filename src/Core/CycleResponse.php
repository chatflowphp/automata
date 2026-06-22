<?php

declare(strict_types=1);

namespace Automata\Core;

use Automata\Contracts\CommandInterface;
use Automata\Contracts\EventInterface;
use Automata\Messages\TransitionCommand;

/**
 * Immutable aggregate describing the result of a processing cycle.
 */
final class CycleResponse
{
    /** @var array<int, CommandInterface> */
    private array $commands;

    /** @var array<int, EventInterface> */
    private array $events;

    /**
     * @param array<int, CommandInterface> $commands
     * @param array<int, EventInterface> $events
     */
    public function __construct(array $commands = [], array $events = [])
    {
        $this->commands = array_is_list($commands) ? $commands : array_values($commands);
        $this->events = array_is_list($events) ? $events : array_values($events);
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
     * @param array<int, CommandInterface> $commands
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
     * @param array<int, EventInterface> $events
     */
    public static function fromEvents(array $events): self
    {
        return new self([], $events);
    }

    public static function transitionTo(string $automatonId): self
    {
        return self::fromCommand(new TransitionCommand($automatonId));
    }

    public function withCommand(CommandInterface $command): self
    {
        return new self([...$this->commands, $command], $this->events);
    }

    /**
     * @param array<int, CommandInterface> $commands
     */
    public function withCommands(array $commands): self
    {
        return new self([...$this->commands, ...$commands], $this->events);
    }

    public function withEvent(EventInterface $event): self
    {
        return new self($this->commands, [...$this->events, $event]);
    }

    /**
     * @param array<int, EventInterface> $events
     */
    public function withEvents(array $events): self
    {
        return new self($this->commands, [...$this->events, ...$events]);
    }

    public function merge(self $response): self
    {
        return new self(
            [...$this->commands, ...$response->commands],
            [...$this->events, ...$response->events]
        );
    }

    /**
     * @return array<int, CommandInterface>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @return array<int, EventInterface>
     */
    public function getEvents(): array
    {
        return $this->events;
    }
}
