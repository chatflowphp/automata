<?php

declare(strict_types=1);

namespace Automata\Tests\Core\State;

use Automata\Core\State\JsonSnapshotSerializer;
use Automata\DTO\StateSnapshot;
use Automata\Exceptions\SnapshotHydrationException;
use PHPUnit\Framework\TestCase;

final class JsonSnapshotSerializerTest extends TestCase
{
    public function testSerializeAndDeserializeSnapshot(): void
    {
        $serializer = new JsonSnapshotSerializer();
        $snapshot = StateSnapshot::fromArray([
            'contextState' => ['status' => 'ready'],
            'fsmAutomatonId' => 'fsm',
            'automataStates' => ['fsm' => ['counter' => 5]],
        ]);

        $payload = $serializer->serialize($snapshot);
        $restored = $serializer->deserialize($payload);

        self::assertSame($snapshot->toArray(), $restored->toArray());
    }

    public function testDeserializeRejectsInvalidJson(): void
    {
        $serializer = new JsonSnapshotSerializer();

        $this->expectException(SnapshotHydrationException::class);
        $this->expectExceptionMessage('Unable to deserialize snapshot');

        $serializer->deserialize('{');
    }

    public function testDeserializeRejectsNonObjectJsonRoot(): void
    {
        $serializer = new JsonSnapshotSerializer();

        $this->expectException(SnapshotHydrationException::class);
        $this->expectExceptionMessage('JSON root must be an object');

        $serializer->deserialize('[]');
    }
}
