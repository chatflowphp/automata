<?php

declare(strict_types=1);

namespace Automata\Contracts;

/**
 * Provides access to a cross-cutting state shared across automaton invocations.
 *
 * Context implementations are responsible for persisting arbitrary key/value pairs and exposing
 * a serialization protocol so the orchestrator can store and restore contextual data.
 */
interface ContextInterface
{
    /**
     * Fetches a value from the context storage.
     *
     * @param string $key Identifier of the value to retrieve.
     * @param mixed|null $default Fallback value returned when the key is not present.
     *
     * @return mixed Value associated with the key or the provided default.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Persists a value inside the context storage.
     *
     * @param string $key Identifier of the value to store.
     * @param mixed $value Arbitrary value that should be persisted for future retrieval.
     *
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Exports the entire contextual state as a serializable array structure.
     *
     * @return array<string, mixed> Associative array containing all stored values.
     */
    public function getState(): array;

    /**
     * Restores the contextual state from a previously exported array.
     *
     * @param array<string, mixed> $state Serialized representation of the context.
     *
     * @return void
     */
    public function setState(array $state): void;
}
