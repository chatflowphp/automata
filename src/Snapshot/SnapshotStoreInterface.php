<?php

declare(strict_types=1);

namespace Automata\Snapshot;

/**
 * Persists snapshots by key, for example one key per chat or per workflow instance.
 */
interface SnapshotStoreInterface
{
    public function load(string $key): ?StateSnapshot;

    public function save(string $key, StateSnapshot $snapshot): void;

    public function delete(string $key): void;
}
