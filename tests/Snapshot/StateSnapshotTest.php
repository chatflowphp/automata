<?php

declare(strict_types=1);

namespace Automata\Tests\Snapshot;

use Automata\Exception\SnapshotHydrationException;
use Automata\Snapshot\StateSnapshot;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use stdClass;

final class StateSnapshotTest extends TestCase
{
    private const CREATED_AT = '2026-09-10T12:00:00+00:00';

    public function testCreateNormalizesValuesAndRoundTripsThroughArray(): void
    {
        $snapshot = StateSnapshot::create(
            ['status' => SnapshotEnum::READY, 'ratio' => 1.0],
            'fsm',
            ['fsm' => ['counter' => 3, 'flag' => SnapshotEnum::READY]],
            7,
            new DateTimeImmutable(self::CREATED_AT),
        );

        $expected = [
            'schemaVersion' => 2,
            'createdAt' => self::CREATED_AT,
            'tickCount' => 7,
            'currentStateId' => 'fsm',
            'contextState' => ['status' => 'ready', 'ratio' => 1.0],
            'stateData' => ['fsm' => ['counter' => 3, 'flag' => 'ready']],
        ];

        self::assertSame($expected, $snapshot->toArray());
        self::assertSame($expected, $snapshot->jsonSerialize());
        self::assertSame($expected, StateSnapshot::fromArray($snapshot->toArray())->toArray());
        self::assertSame(self::CREATED_AT, $snapshot->createdAt->format(DATE_ATOM));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidArrays(): iterable
    {
        $valid = [
            'schemaVersion' => 2,
            'createdAt' => self::CREATED_AT,
            'tickCount' => 0,
            'currentStateId' => null,
            'contextState' => [],
            'stateData' => [],
        ];

        yield 'old version' => [['schemaVersion' => 1] + $valid, 'Unsupported snapshot schema version 1, expected 2.'];
        yield 'missing version' => [array_diff_key($valid, ['schemaVersion' => 0]), 'Unsupported snapshot schema version null, expected 2.'];
        yield 'missing context' => [array_diff_key($valid, ['contextState' => 0]), '"contextState" must be present and must be an array.'];
        yield 'bad current state' => [['currentStateId' => 5] + $valid, '"currentStateId" must be a string or null.'];
        yield 'empty current state' => [['currentStateId' => ''] + $valid, '"currentStateId" must be a non-empty string or null.'];
        yield 'missing state data' => [array_diff_key($valid, ['stateData' => 0]), '"stateData" must be present and must be an array.'];
        yield 'bad tick count' => [['tickCount' => '1'] + $valid, '"tickCount" must be an integer.'];
        yield 'negative tick count' => [['tickCount' => -1] + $valid, '"tickCount" must not be negative.'];
        yield 'bad created at type' => [['createdAt' => 1] + $valid, '"createdAt" must be an ISO-8601 string.'];
        yield 'bad created at format' => [['createdAt' => 'yesterday'] + $valid, '"createdAt" value "yesterday" is not ISO-8601.'];
        yield 'int keys in state data' => [['stateData' => [['x' => 1]]] + $valid, '"stateData" keys must be non-empty strings.'];
        yield 'scalar state data' => [['stateData' => ['fsm' => 1]] + $valid, 'data for state "fsm" must be an array.'];
        yield 'int keys in context' => [['contextState' => ['a']] + $valid, 'Invalid state at "contextState": keys must be non-numeric strings, got 0.'];
        yield 'object in context' => [['contextState' => ['o' => new stdClass()]] + $valid, 'value must be scalar, array, backed enum, or null, got stdClass.'];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @dataProvider invalidArrays
     */
    public function testFromArrayRejectsInvalidStructures(array $data, string $message): void
    {
        $this->expectException(SnapshotHydrationException::class);
        $this->expectExceptionMessage($message);

        StateSnapshot::fromArray($data);
    }
}

enum SnapshotEnum: string
{
    case READY = 'ready';
}
