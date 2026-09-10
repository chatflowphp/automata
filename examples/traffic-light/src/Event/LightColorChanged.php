<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\Event;

use Automata\Messaging\EventInterface;

final class LightColorChanged implements EventInterface
{
    public function __construct(
        public readonly string $fromColor,
        public readonly string $toColor,
    ) {}
}
