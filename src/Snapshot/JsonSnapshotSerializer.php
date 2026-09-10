<?php

declare(strict_types=1);

namespace Automata\Snapshot;

use Automata\Exception\SnapshotHydrationException;
use JsonException;

/**
 * JSON wire format with schema migrations.
 *
 * Floats keep their type through a round trip (JSON_PRESERVE_ZERO_FRACTION). Payloads without a
 * "schemaVersion" field are treated as version 1 and require a registered migration.
 */
final class JsonSnapshotSerializer implements SnapshotSerializerInterface
{
    /**
     * @var array<int, SnapshotMigrationInterface>
     */
    private array $migrations = [];

    /**
     * @param iterable<SnapshotMigrationInterface> $migrations
     * @param int $encodeFlags Extra json_encode() flags, for example JSON_PRETTY_PRINT.
     */
    public function __construct(iterable $migrations = [], private readonly int $encodeFlags = 0)
    {
        foreach ($migrations as $migration) {
            $this->migrations[$migration->fromVersion()] = $migration;
        }
    }

    public function serialize(StateSnapshot $snapshot): string
    {
        try {
            return json_encode(
                $snapshot,
                JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | $this->encodeFlags,
            );
        } catch (JsonException $exception) {
            throw new SnapshotHydrationException(
                'Unable to serialize snapshot: ' . $exception->getMessage(),
                previous: $exception,
            );
        }
    }

    public function deserialize(string $payload): StateSnapshot
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new SnapshotHydrationException(
                'Unable to deserialize snapshot: ' . $exception->getMessage(),
                previous: $exception,
            );
        }

        if (!\is_array($decoded) || array_is_list($decoded)) {
            throw new SnapshotHydrationException('Unable to deserialize snapshot: JSON root must be a non-empty object.');
        }

        /** @var array<string, mixed> $decoded */
        return StateSnapshot::fromArray($this->migrate($decoded));
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    private function migrate(array $raw): array
    {
        $version = $raw['schemaVersion'] ?? 1;

        if (!\is_int($version)) {
            throw new SnapshotHydrationException('Invalid snapshot: "schemaVersion" must be an integer.');
        }

        while ($version < StateSnapshot::SCHEMA_VERSION) {
            $migration = $this->migrations[$version] ?? throw new SnapshotHydrationException(\sprintf(
                'No migration registered for snapshot schema version %d.',
                $version,
            ));

            $raw = $migration->migrate($raw);
            $next = $raw['schemaVersion'] ?? null;

            if (!\is_int($next) || $next <= $version) {
                throw new SnapshotHydrationException(\sprintf(
                    'Migration from schema version %d must set a higher "schemaVersion".',
                    $version,
                ));
            }

            $version = $next;
        }

        return $raw;
    }
}
