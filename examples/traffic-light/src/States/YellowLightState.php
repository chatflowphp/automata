<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\States;

use AutomataExamples\TrafficLight\Enum\TrafficLightStatus;

final class YellowLightState extends LightState
{
    protected function status(): TrafficLightStatus
    {
        return TrafficLightStatus::YELLOW;
    }
}
