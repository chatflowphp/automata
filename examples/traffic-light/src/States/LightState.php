<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\States;

use Automata\Context\ContextInterface;
use Automata\Machine\CycleResponse;
use Automata\Machine\InputInterface;
use Automata\State\AbstractState;
use AutomataExamples\TrafficLight\Command\ChangeColorCommand;
use AutomataExamples\TrafficLight\Enum\TrafficLightStatus;
use AutomataExamples\TrafficLight\Event\LightColorChanged;
use AutomataExamples\TrafficLight\TimerInput;

/**
 * Shared behaviour of the three lights: stay on for the configured number of ticks, then switch.
 *
 * @extends AbstractState<TimerInput>
 */
abstract class LightState extends AbstractState
{
    protected const INPUT = TimerInput::class;

    abstract protected function status(): TrafficLightStatus;

    public function getId(): string
    {
        return $this->status()->value;
    }

    public function onEnter(ContextInterface $context): CycleResponse
    {
        $context->set('current_color', $this->status()->color());
        $context->set('ticks_in_state', 0);

        return CycleResponse::none();
    }

    protected function handle(InputInterface $input, ContextInterface $context): CycleResponse
    {
        $ticksInState = $context->increment('ticks_in_state');

        if ($ticksInState < $this->status()->duration()) {
            return CycleResponse::none();
        }

        $next = $this->status()->next();

        return CycleResponse::fromCommand(new ChangeColorCommand($next))
            ->withEvent(new LightColorChanged($this->status()->color(), $next->color()));
    }
}
