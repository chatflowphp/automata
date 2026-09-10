<?php

declare(strict_types=1);

namespace Automata\Tests\Clock;

use Automata\Clock\FrozenClock;
use Automata\Clock\SystemClock;
use PHPUnit\Framework\TestCase;

final class FrozenClockTest extends TestCase
{
    public function testFrozenClockIsStableUntilAdvanced(): void
    {
        $clock = FrozenClock::at('2026-09-10T12:00:00+00:00');

        self::assertSame('2026-09-10T12:00:00+00:00', $clock->now()->format(DATE_ATOM));
        self::assertSame($clock->now(), $clock->now());

        $clock->advance('+1 hour');

        self::assertSame('2026-09-10T13:00:00+00:00', $clock->now()->format(DATE_ATOM));
    }

    public function testSystemClockReturnsUtc(): void
    {
        self::assertSame('UTC', (new SystemClock())->now()->getTimezone()->getName());
    }
}
