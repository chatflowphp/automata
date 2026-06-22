<?php

declare(strict_types=1);

namespace Automata\Contracts;

/**
 * Contract for registries capable of storing and resolving automata instances.
 */
interface AutomatonRegistryInterface
{
    public function register(AutomatonInterface $automaton): void;

    public function getAutomaton(string $id): AutomatonInterface;

    public function hasAutomaton(string $id): bool;

    /**
     * @return array<string, AutomatonInterface>
     */
    public function all(): array;
}
