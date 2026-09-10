<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight;

use Automata\Machine\InputInterface;

final class TimerInput implements InputInterface
{
    public function __construct(public readonly int $tick) {}
}
