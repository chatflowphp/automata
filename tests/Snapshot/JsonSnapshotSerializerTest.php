<?php

declare(strict_types=1);

namespace Automata\Tests\Snapshot;

use Automata\Clock\FrozenClock;
use Automata\Exception\SnapshotHydrationException;
use Automata\Snapshot\JsonSnapshotSerializer;
use Automata\Snapshot\Migration\LegacyV1Migration;
use Automata\Snapshot\SnapshotMigrationInterface;
use Automata\Snapshot\StateSnapshot;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class JsonSnapshotSerializerTest extends TestCase
{
    public function testRoundTripKeepsFloatType(): void
    {
        $serializer = new JsonSnapshotSerializer();
        $snapshot = StateSnapshot::create(
            ['ratio' => 1.0, 'count' => 1, 'items' => ['a', 'b'], 'map' => ['k' => 'v']],
            'fsm',
            ['fsm' => ['counter' => 5]],
            2,
            new DateTimeImmutable('2026-09-10T12:00:00+00:00'),
        );

        $payload = $serializer->serialize($snapshot);
        $restored = $serializer->deserialize($payload);

        self::assertStringContainsString('"ratio":1.0', $payload);
        self::assertSame(1.0, $restored->contextState['ratio']);
        self::assertSame(1, $restored->contextState['count']);
        self::assertSame($snapshot->toArray(), $restored->toArray());
    }

    public function testEncodeFlagsAreApplied(): void
    {
        $serializer = new JsonSnapshotSerializer(encodeFlags: JSON_PRETTY_PRINT);
        $snapshot = StateSnapshot::create([], null, [], 0, new DateTimeImmutable('2026-09-10T12:00:00+00:00'));

        self::assertStringContainsString("\n", $serializer->serialize($snapshot));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'broken json' => ['{', 'Unable to deserialize snapshot: Syntax error'];
        yield 'list root' => ['[1]', 'JSON root must be a non-empty object.'];
        yield 'empty object' => ['{}', 'JSON root must be a non-empty object.'];
        yield 'scalar root' => ['"x"', 'JSON root must be a non-empty object.'];
        yield 'string version' => ['{"schemaVersion":"2"}', '"schemaVersion" must be an integer.'];
        yield 'missing migration' => ['{"contextState":{},"fsmAutomatonId":null,"automataStates":{}}', 'No migration registered for snapshot schema version 1.'];
    }

    /**
     * @dataProvider invalidPayloads
     */
    public function testDeserializeRejectsInvalidPayloads(string $payload, string $message): void
    {
        $serializer = new JsonSnapshotSerializer();

        $this->expectException(SnapshotHydrationException::class);
        $this->expectExceptionMessage($message);

        $serializer->deserialize($payload);
    }

    public function testLegacyV1PayloadIsMigrated(): void
    {
        $clock = FrozenClock::at('2026-09-10T12:00:00+00:00');
        $serializer = new JsonSnapshotSerializer([new LegacyV1Migration($clock)]);

        $legacy = '{"contextState":{"cycle_count":1},"fsmAutomatonId":"workflow.active","automataStates":{"workflow.active":{"n":2}}}';
        $snapshot = $serializer->deserialize($legacy);

        self::assertSame([
            'schemaVersion' => 2,
            'createdAt' => '2026-09-10T12:00:00+00:00',
            'tickCount' => 0,
            'currentStateId' => 'workflow.active',
            'contextState' => ['cycle_count' => 1],
            'stateData' => ['workflow.active' => ['n' => 2]],
        ], $snapshot->toArray());
    }

    public function testLegacyMigrationRejectsUnknownShape(): void
    {
        $migration = new LegacyV1Migration();

        $this->expectException(SnapshotHydrationException::class);
        $this->expectExceptionMessage('Legacy snapshot must contain "fsmAutomatonId" and "automataStates".');

        $migration->migrate(['contextState' => []]);
    }

    public function testMigrationsAreChainedInOrder(): void
    {
        $serializer = new JsonSnapshotSerializer([
            new ChainMigration(1, 0),
            new ChainMigration(0, 1),
        ]);

        $snapshot = $serializer->deserialize('{"schemaVersion":0,"contextState":{"steps":[]}}');

        self::assertSame(['steps' => ['0->1', '1->2']], $snapshot->contextState);
    }

    public function testMigrationMustRaiseTheVersion(): void
    {
        $serializer = new JsonSnapshotSerializer([new StuckMigration()]);

        $this->expectException(SnapshotHydrationException::class);
        $this->expectExceptionMessage('Migration from schema version 1 must set a higher "schemaVersion".');

        $serializer->deserialize('{"contextState":{}}');
    }
}

final class ChainMigration implements SnapshotMigrationInterface
{
    public function __construct(private readonly int $from, private readonly int $order) {}

    public function fromVersion(): int
    {
        return $this->from;
    }

    public function migrate(array $raw): array
    {
        $context = \is_array($raw['contextState'] ?? null) ? $raw['contextState'] : [];
        $steps = \is_array($context['steps'] ?? null) ? $context['steps'] : [];
        $steps[] = \sprintf('%d->%d', $this->from, $this->from + 1);
        $context['steps'] = $steps;

        $raw['contextState'] = $context;
        $raw['schemaVersion'] = $this->from + 1;

        if ($this->from + 1 === StateSnapshot::SCHEMA_VERSION) {
            $raw += ['createdAt' => '2026-09-10T12:00:00+00:00', 'tickCount' => $this->order, 'currentStateId' => null, 'stateData' => []];
        }

        return $raw;
    }
}

final class StuckMigration implements SnapshotMigrationInterface
{
    public function fromVersion(): int
    {
        return 1;
    }

    public function migrate(array $raw): array
    {
        return $raw;
    }
}
