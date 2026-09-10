<?php

declare(strict_types=1);

namespace AutomataExamples\SimpleWorkflow\Event;

use Automata\Messaging\EventInterface;

final class WorkflowAdvanced implements EventInterface
{
    public function __construct(
        public readonly string $fromState,
        public readonly string $toState,
    ) {}
}
