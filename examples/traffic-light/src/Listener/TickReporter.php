<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\Listener;

use Automata\Events\TickCompleted;
use AutomataExamples\TrafficLight\Output;
use AutomataExamples\TrafficLight\TimerInput;

final class TickReporter
{
    public function __construct(private readonly Output $output) {}

    public function __invoke(TickCompleted $event): void
    {
        $input = $event->request->getInput();
        $context = $event->request->getContext();

        $this->output->add(\sprintf(
            '   tick %s -> color=%s | total_ticks=%d | state=%s',
            $input instanceof TimerInput ? (string) $input->tick : '?',
            strtoupper($context->getString('current_color', 'unknown')),
            $context->getInt('total_ticks'),
            $event->result->toStateId,
        ));
    }
}
