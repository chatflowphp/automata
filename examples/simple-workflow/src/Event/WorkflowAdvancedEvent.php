<?php

declare(strict_types=1);

namespace AutomataExamples\SimpleWorkflow\Event;

use Automata\Contracts\EventInterface;

final class WorkflowAdvancedEvent implements EventInterface
{
    public const NAME = 'simple_workflow.advanced';

    public function __construct(
        public readonly string $fromState,
        public readonly string $toState
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array{from_state: string, to_state: string}
     */
    public function getPayload(): array
    {
        return [
            'from_state' => $this->fromState,
            'to_state' => $this->toState,
        ];
    }
}
