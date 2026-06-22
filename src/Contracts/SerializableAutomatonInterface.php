<?php

declare(strict_types=1);

namespace Automata\Contracts;

/**
 * Extension point for automata that maintain internal state requiring persistence.
 *
 * Implementations rely on the orchestrator to serialize the state between invocations.
 */
interface SerializableAutomatonInterface extends AutomatonInterface
{
    /**
     * Exports the internal automaton state so it can be persisted externally.
     *
     * @return array<string, mixed> Serializable representation of internal state.
     */
    public function getState(): array;

    /**
     * Restores the internal automaton state from previously exported data.
     *
     * @param array<string, mixed> $state Serializable representation of internal state.
     *
     * @return void
     */
    public function setState(array $state): void;
}
