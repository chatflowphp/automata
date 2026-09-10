<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\Enum;

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

    public function next(): self
    {
        return match ($this) {
            self::RED => self::GREEN,
            self::GREEN => self::YELLOW,
            self::YELLOW => self::RED,
        };
    }

    /**
     * How many ticks the light stays on before switching.
     */
    public function duration(): int
    {
        return match ($this) {
            self::RED => 2,
            self::GREEN => 2,
            self::YELLOW => 1,
        };
    }
}
