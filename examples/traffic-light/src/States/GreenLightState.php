<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\States;

use Automata\Contracts\AutomatonInterface;
use Automata\Contracts\ContextInterface;
use Automata\Core\CycleRequest;
use Automata\Core\CycleResponse;
use AutomataExamples\TrafficLight\Command\ChangeColorCommand;
use AutomataExamples\TrafficLight\Enum\TrafficLightStatus;
use AutomataExamples\TrafficLight\Event\LightColorChangedEvent;

final class GreenLightState implements AutomatonInterface
{
    private const STATUS = TrafficLightStatus::GREEN;

    public function getId(): string
    {
        return self::STATUS->value;
    }

    public function onEnter(ContextInterface $context): void
    {
        $context->set('current_color', self::STATUS->color());
        $context->set('active_automaton_hint', self::STATUS->value);
    }

    public function process(CycleRequest $request): CycleResponse
    {
        $context = $request->getContext();
        $totalTicks = $context->get('total_ticks');
        if (!is_int($totalTicks)) {
            $totalTicks = 0;
        }

        if ($totalTicks % 2 !== 0) {
            return CycleResponse::none();
        }

        $nextStatus = TrafficLightStatus::YELLOW;
        $nextColor = $nextStatus->color();

        $command = new ChangeColorCommand($nextColor, $nextStatus->value);

        $event = new LightColorChangedEvent(self::STATUS->color(), $nextColor);

        return CycleResponse::fromCommand($command)->withEvent($event);
    }

    public function onLeave(ContextInterface $context): void
    {
        // no-op
    }
}
