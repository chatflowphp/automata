<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight;

use Automata\Contracts\InputInterface;

final class TimerInput implements InputInterface
{
    public function __construct(private readonly int $tick)
    {
    }

    public function getTick(): int
    {
        return $this->tick;
    }
}
