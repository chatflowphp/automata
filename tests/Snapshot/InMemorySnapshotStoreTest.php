<?php

declare(strict_types=1);

namespace Automata\Tests\Snapshot;

use Automata\Snapshot\InMemorySnapshotStore;
use Automata\Snapshot\StateSnapshot;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class InMemorySnapshotStoreTest extends TestCase
{
    public function testSaveLoadDelete(): void
    {
        $store = new InMemorySnapshotStore();
        $snapshot = StateSnapshot::create([], 'a', [], 0, new DateTimeImmutable('2026-09-10T12:00:00+00:00'));

        self::assertNull($store->load('k'));

        $store->save('k', $snapshot);

        self::assertSame($snapshot, $store->load('k'));
        self::assertSame(['k'], $store->keys());

        $store->delete('k');

        self::assertNull($store->load('k'));
        self::assertSame([], $store->keys());
    }
}
