<?php

declare(strict_types=1);

namespace Automata\Tests\DTO;

use Automata\DTO\StateSnapshot;
use Automata\Exceptions\SnapshotHydrationException;
use PHPUnit\Framework\TestCase;

final class StateSnapshotTest extends TestCase
{
    public function testSnapshotCanRoundTripThroughArrayAndJson(): void
    {
        $snapshot = StateSnapshot::fromArray([
            'contextState' => ['status' => 'ready'],
            'fsmAutomatonId' => 'fsm',
            'automataStates' => ['fsm' => ['counter' => 3]],
        ]);

        self::assertSame(
            [
                'contextState' => ['status' => 'ready'],
                'fsmAutomatonId' => 'fsm',
                'automataStates' => ['fsm' => ['counter' => 3]],
            ],
            $snapshot->toArray()
        );

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) json_encode($snapshot, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $restored = StateSnapshot::fromArray($decoded);

        self::assertSame($snapshot->toArray(), $restored->toArray());
    }

    public function testSnapshotRejectsInvalidStructure(): void
    {
        $this->expectException(SnapshotHydrationException::class);
        $this->expectExceptionMessage('"contextState" must be present and must be an array');

        StateSnapshot::fromArray([
            'fsmAutomatonId' => 'fsm',
            'automataStates' => [],
        ]);
    }

    public function testSnapshotRejectsNonStringAutomataKeys(): void
    {
        $this->expectException(SnapshotHydrationException::class);
        $this->expectExceptionMessage('automataStates keys must be non-empty strings');

        StateSnapshot::fromArray([
            'contextState' => [],
            'fsmAutomatonId' => null,
            'automataStates' => [
                0 => ['counter' => 1],
            ],
        ]);
    }
}
