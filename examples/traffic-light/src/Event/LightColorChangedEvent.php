<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\Event;

use Automata\Contracts\EventInterface;

/**
 * Domain event emitted whenever the traffic light changes its color.
 */
final class LightColorChangedEvent implements EventInterface
{
    public const NAME = 'traffic_light.color_changed';

    public function __construct(
        public readonly string $fromColor,
        public readonly string $toColor
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array{from_color: string, to_color: string}
     */
    public function getPayload(): array
    {
        return [
            'from_color' => $this->fromColor,
            'to_color' => $this->toColor,
        ];
    }
}
