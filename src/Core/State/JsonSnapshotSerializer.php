<?php

declare(strict_types=1);

namespace Automata\Core\State;

use Automata\Contracts\SnapshotSerializerInterface;
use Automata\DTO\StateSnapshot;
use Automata\Exceptions\SnapshotHydrationException;
use JsonException;

final class JsonSnapshotSerializer implements SnapshotSerializerInterface
{
    public function serialize(StateSnapshot $snapshot): string
    {
        try {
            return json_encode($snapshot, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new SnapshotHydrationException(
                sprintf('Unable to serialize snapshot: %s', $exception->getMessage()),
                previous: $exception
            );
        }
    }

    public function deserialize(string $payload): StateSnapshot
    {
        try {
            $root = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new SnapshotHydrationException(
                sprintf('Unable to deserialize snapshot: %s', $exception->getMessage()),
                previous: $exception
            );
        }

        if (!$root instanceof \stdClass) {
            throw new SnapshotHydrationException('Unable to deserialize snapshot: JSON root must be an object.');
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        return StateSnapshot::fromArray($decoded);
    }
}
