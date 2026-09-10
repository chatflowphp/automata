<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\States;

use AutomataExamples\TrafficLight\Enum\TrafficLightStatus;

final class RedLightState extends LightState
{
    protected function status(): TrafficLightStatus
    {
        return TrafficLightStatus::RED;
    }
}
