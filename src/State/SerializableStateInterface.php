<?php

declare(strict_types=1);

namespace Automata\State;

/**
 * A state that owns internal data which must survive snapshot() and restore().
 *
 * Prefer ContextInterface for data shared between states. Use this only for data that belongs
 * to a single state.
 */
interface SerializableStateInterface extends StateInterface
{
    /**
     * @return array<string, mixed> Snapshot-safe values: scalars, arrays of scalars, backed enums, null.
     */
    public function getState(): array;

    /**
     * @param array<string, mixed> $state
     */
    public function setState(array $state): void;
}
