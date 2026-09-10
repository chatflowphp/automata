<?php

declare(strict_types=1);

namespace Automata\Snapshot;

/**
 * Store that keeps snapshots in a PHP array. Suitable for tests and single-process workers.
 */
final class InMemorySnapshotStore implements SnapshotStoreInterface
{
    /**
     * @var array<string, StateSnapshot>
     */
    private array $snapshots = [];

    public function load(string $key): ?StateSnapshot
    {
        return $this->snapshots[$key] ?? null;
    }

    public function save(string $key, StateSnapshot $snapshot): void
    {
        $this->snapshots[$key] = $snapshot;
    }

    public function delete(string $key): void
    {
        unset($this->snapshots[$key]);
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->snapshots);
    }
}
