<?php

declare(strict_types=1);

namespace Automata\Snapshot;

/**
 * Upgrades the raw array form of a snapshot from one schema version to a higher one.
 *
 * The returned array must carry the new "schemaVersion". Migrations are chained by the serializer
 * until StateSnapshot::SCHEMA_VERSION is reached.
 */
interface SnapshotMigrationInterface
{
    public function fromVersion(): int;

    /**
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    public function migrate(array $raw): array;
}
