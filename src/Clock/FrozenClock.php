<?php

declare(strict_types=1);

namespace Automata\Clock;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Clock that always returns the same instant. Intended for tests and deterministic examples.
 */
final class FrozenClock implements ClockInterface
{
    public function __construct(private DateTimeImmutable $now) {}

    public static function at(string $time): self
    {
        return new self(new DateTimeImmutable($time));
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(string $interval): void
    {
        $this->now = $this->now->modify($interval);
    }
}
