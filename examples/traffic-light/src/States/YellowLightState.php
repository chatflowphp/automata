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

final class YellowLightState implements AutomatonInterface
{
    private const STATUS = TrafficLightStatus::YELLOW;
    private const CONTEXT_KEY_TICKS = 'yellow_ticks';
    private const REQUIRED_TICKS = 3;

    public function getId(): string
    {
        return self::STATUS->value;
    }

    public function onEnter(ContextInterface $context): void
    {
        $context->set('current_color', self::STATUS->color());
        $context->set('active_automaton_hint', self::STATUS->value);
        $context->set(self::CONTEXT_KEY_TICKS, 0);
    }

    public function process(CycleRequest $request): CycleResponse
    {
        $context = $request->getContext();
        $storedTicks = $context->get(self::CONTEXT_KEY_TICKS);
        if (!is_int($storedTicks)) {
            $storedTicks = 0;
        }

        $ticksInYellow = $storedTicks + 1;
        $context->set(self::CONTEXT_KEY_TICKS, $ticksInYellow);

        if ($ticksInYellow < self::REQUIRED_TICKS) {
            return CycleResponse::none();
        }

        $nextStatus = TrafficLightStatus::RED;
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
