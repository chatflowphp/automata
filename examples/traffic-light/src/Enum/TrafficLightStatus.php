<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\Enum;

/**
 * Enumerates traffic light states with helpers for color and transitions.
 */
enum TrafficLightStatus: string
{
    case RED = 'traffic_light.red';
    case GREEN = 'traffic_light.green';
    case YELLOW = 'traffic_light.yellow';

    public function color(): string
    {
        return match ($this) {
            self::RED => 'red',
            self::GREEN => 'green',
            self::YELLOW => 'yellow',
        };
    }
}
