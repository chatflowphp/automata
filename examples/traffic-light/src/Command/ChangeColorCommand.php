<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\Command;

use Automata\Messaging\TransitionCommandInterface;
use AutomataExamples\TrafficLight\Enum\TrafficLightStatus;

/**
 * A domain command that is also a transition: the machine switches state, then dispatches it.
 */
final class ChangeColorCommand implements TransitionCommandInterface
{
    public function __construct(public readonly TrafficLightStatus $to) {}

    public function getTargetStateId(): string
    {
        return $this->to->value;
    }
}
