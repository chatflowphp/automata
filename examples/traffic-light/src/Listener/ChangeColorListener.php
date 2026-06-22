<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\Listener;

use AutomataExamples\TrafficLight\Application\ResponseSimple;
use AutomataExamples\TrafficLight\Event\LightColorChangedEvent;
use AutomataExamples\TrafficLight\TrafficLightContext;

/**
 * Logs traffic light color transitions emitted as domain events.
 */
final class ChangeColorListener
{
    public function __construct(
        private ResponseSimple $response,
        private TrafficLightContext $context
    ) {
    }

    public function __invoke(LightColorChangedEvent $event): void
    {
        $payload = $event->getPayload();
        $currentColor = $this->context->get('current_color', 'unknown');
        $activeAutomaton = $this->context->get('active_automaton_hint', 'unknown');

        $this->response->add(sprintf(
            "[%s] Traffic light changed from %s to %s",
            date('Y-m-d H:i:s'),
            strtoupper($payload['from_color']),
            strtoupper($payload['to_color'])
        ));
        $this->response->add(sprintf(
            "   Transition observed after activation -> color=%s | active=%s",
            strtoupper(is_string($currentColor) ? $currentColor : 'unknown'),
            is_string($activeAutomaton) ? $activeAutomaton : 'unknown'
        ));
    }
}
