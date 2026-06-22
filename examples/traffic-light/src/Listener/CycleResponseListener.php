<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\Listener;

use Automata\Events\CycleCompletedEvent;
use AutomataExamples\TrafficLight\Application\ResponseSimple;
use AutomataExamples\TrafficLight\TimerInput;

final class CycleResponseListener
{
    public function __construct(private ResponseSimple $response)
    {
    }

    public function __invoke(CycleCompletedEvent $event): void
    {
        $request = $event->getRequest();

        $input = $request->getInput();
        $context = $request->getContext();
        $tick = $input instanceof TimerInput ? $input->getTick() : null;

        $color = $context->get('current_color');
        if (!is_string($color)) {
            $color = 'unknown';
        }

        $totalTicks = $context->get('total_ticks');
        if (!is_int($totalTicks)) {
            $totalTicks = 0;
        }
        $parity = $totalTicks % 2 === 0 ? 'even' : 'odd';

        if ($tick !== null && $tick <= 0) {
            return;
        }

        $this->response->add(
            sprintf(
                "   State -> color=%s | total_ticks=%d (%s)",
                strtoupper($color),
                $totalTicks,
                $parity
            )
        );
    }
}
