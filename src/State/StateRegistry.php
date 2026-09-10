<?php

declare(strict_types=1);

namespace Automata\State;

use Automata\Exception\DuplicateStateException;
use Automata\Exception\StateNotFoundException;

/**
 * Default in-memory state registry.
 */
final class StateRegistry implements StateRegistryInterface
{
    /**
     * @var array<string, StateInterface>
     */
    private array $states = [];

    public function register(StateInterface $state): void
    {
        $id = $state->getId();

        if (isset($this->states[$id])) {
            throw DuplicateStateException::forId($id);
        }

        $this->states[$id] = $state;
    }

    public function replace(StateInterface $state): void
    {
        $this->states[$state->getId()] = $state;
    }

    public function get(string $stateId): StateInterface
    {
        return $this->states[$stateId] ?? throw StateNotFoundException::forId($stateId);
    }

    public function has(string $stateId): bool
    {
        return isset($this->states[$stateId]);
    }

    public function all(): array
    {
        return $this->states;
    }
}
