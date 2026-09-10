<?php

declare(strict_types=1);

namespace Automata\Snapshot\Migration;

use Automata\Clock\SystemClock;
use Automata\Exception\SnapshotHydrationException;
use Automata\Snapshot\SnapshotMigrationInterface;
use DateTimeInterface;
use Psr\Clock\ClockInterface;

/**
 * Migrates snapshots written by chatflowphp/automata 1.x, which had no "schemaVersion" field.
 *
 * 1.x shape: {"contextState": {}, "fsmAutomatonId": "id"|null, "automataStates": {}}
 */
final class LegacyV1Migration implements SnapshotMigrationInterface
{
    public function __construct(private readonly ClockInterface $clock = new SystemClock()) {}

    public function fromVersion(): int
    {
        return 1;
    }

    public function migrate(array $raw): array
    {
        if (!\array_key_exists('fsmAutomatonId', $raw) || !\array_key_exists('automataStates', $raw)) {
            throw new SnapshotHydrationException('Legacy snapshot must contain "fsmAutomatonId" and "automataStates".');
        }

        return [
            'schemaVersion' => 2,
            'createdAt' => $this->clock->now()->format(DateTimeInterface::ATOM),
            'tickCount' => 0,
            'currentStateId' => $raw['fsmAutomatonId'],
            'contextState' => $raw['contextState'] ?? [],
            'stateData' => $raw['automataStates'],
        ];
    }
}
