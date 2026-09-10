<?php

declare(strict_types=1);

namespace Automata\State;

use Automata\Exception\DuplicateStateException;
use Automata\Exception\StateNotFoundException;

/**
 * Stores states by id.
 */
interface StateRegistryInterface
{
    /**
     * @throws DuplicateStateException
     */
    public function register(StateInterface $state): void;

    /**
     * Registers the state, overriding any state with the same id.
     */
    public function replace(StateInterface $state): void;

    /**
     * @throws StateNotFoundException
     */
    public function get(string $stateId): StateInterface;

    public function has(string $stateId): bool;

    /**
     * @return array<string, StateInterface>
     */
    public function all(): array;
}
