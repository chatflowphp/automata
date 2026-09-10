<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\Listener;

use Automata\Context\ContextInterface;
use AutomataExamples\TrafficLight\Event\LightColorChanged;
use AutomataExamples\TrafficLight\Output;
use Psr\Clock\ClockInterface;

/**
 * Runs after the transition committed, so the context already describes the new light.
 */
final class ChangeColorListener
{
    public function __construct(
        private readonly Output $output,
        private readonly ContextInterface $context,
        private readonly ClockInterface $clock,
    ) {}

    public function __invoke(LightColorChanged $event): void
    {
        $this->output->add(\sprintf(
            '[%s] Light changed from %s to %s (context now: %s)',
            $this->clock->now()->format('H:i:s'),
            strtoupper($event->fromColor),
            strtoupper($event->toColor),
            strtoupper($this->context->getString('current_color', 'unknown')),
        ));
    }
}
