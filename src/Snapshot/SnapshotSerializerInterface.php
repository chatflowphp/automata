<?php

declare(strict_types=1);

namespace Automata\Snapshot;

use Automata\Exception\SnapshotHydrationException;

/**
 * Converts snapshots to and from a wire format.
 */
interface SnapshotSerializerInterface
{
    /**
     * @throws SnapshotHydrationException
     */
    public function serialize(StateSnapshot $snapshot): string;

    /**
     * @throws SnapshotHydrationException
     */
    public function deserialize(string $payload): StateSnapshot;
}
