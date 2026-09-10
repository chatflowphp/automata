<?php

declare(strict_types=1);

namespace AutomataExamples\SimpleWorkflow\Application;

use Automata\Context\ArrayContext;
use Automata\Machine\StateMachine;
use AutomataExamples\SimpleWorkflow\Event\WorkflowAdvanced;
use AutomataExamples\SimpleWorkflow\Middleware\CycleCounterMiddleware;
use AutomataExamples\SimpleWorkflow\States\ActiveState;
use AutomataExamples\SimpleWorkflow\States\IdleState;
use Psr\Clock\ClockInterface;

final class SimpleWorkflowApplication
{
    private readonly ArrayContext $context;

    private readonly StateMachine $machine;

    public function __construct(?ClockInterface $clock = null)
    {
        $this->context = new ArrayContext([
            'cycle_count' => 0,
            'workflow_status' => 'not_started',
        ]);
        $this->machine = new StateMachine($this->context, clock: $clock);

        $this->machine->registerMiddleware(new CycleCounterMiddleware());
        $this->machine->registerStates(new IdleState(), new ActiveState());

        $this->machine->subscribe(WorkflowAdvanced::class, function (WorkflowAdvanced $event): void {
            $this->context->push('transition_log', \sprintf(
                '%s->%s|current=%s|cycles=%d',
                $event->fromState,
                $event->toState,
                $this->machine->getCurrentStateId() ?? 'none',
                $this->context->getInt('cycle_count'),
            ));
        });
    }

    public function getContext(): ArrayContext
    {
        return $this->context;
    }

    public function getMachine(): StateMachine
    {
        return $this->machine;
    }

    public function cycleCount(): int
    {
        return $this->context->getInt('cycle_count');
    }

    public function workflowStatus(): string
    {
        return $this->context->getString('workflow_status', 'unknown');
    }

    /**
     * @return list<string>
     */
    public function transitionLog(): array
    {
        return array_values(array_filter($this->context->getList('transition_log'), 'is_string'));
    }
}
