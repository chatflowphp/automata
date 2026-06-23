<?php

declare(strict_types=1);

namespace AutomataExamples\SimpleWorkflow\Application;

use Automata\Core\Context\ArrayContext;
use Automata\Core\Orchestrator;
use AutomataExamples\SimpleWorkflow\Event\WorkflowAdvancedEvent;
use AutomataExamples\SimpleWorkflow\Middleware\CycleCounterMiddleware;
use AutomataExamples\SimpleWorkflow\States\ActiveState;
use AutomataExamples\SimpleWorkflow\States\IdleState;

final class SimpleWorkflowApplication
{
    private ArrayContext $context;

    private Orchestrator $orchestrator;

    public function __construct()
    {
        $this->context = new ArrayContext([
            'cycle_count' => 0,
            'workflow_status' => 'not_started',
            'transition_log' => [],
        ]);
        $this->orchestrator = new Orchestrator($this->context);

        $this->orchestrator->registerMiddleware(new CycleCounterMiddleware());
        $this->orchestrator->registerAutomaton(new IdleState());
        $this->orchestrator->registerAutomaton(new ActiveState());

        $this->orchestrator->subscribe(WorkflowAdvancedEvent::NAME, function (WorkflowAdvancedEvent $event): void {
            $log = $this->transitionLog();
            $log[] = sprintf(
                '%s->%s|active=%s|cycles=%d',
                $event->fromState,
                $event->toState,
                $this->orchestrator->getActiveAutomatonId() ?? 'none',
                $this->cycleCount()
            );

            $this->context->set('transition_log', $log);
        });
    }

    public function getContext(): ArrayContext
    {
        return $this->context;
    }

    public function getOrchestrator(): Orchestrator
    {
        return $this->orchestrator;
    }

    public function cycleCount(): int
    {
        $count = $this->context->get('cycle_count', 0);

        return is_int($count) ? $count : 0;
    }

    public function workflowStatus(): string
    {
        $status = $this->context->get('workflow_status', 'unknown');

        return is_string($status) ? $status : 'unknown';
    }

    /**
     * @return list<string>
     */
    public function transitionLog(): array
    {
        $log = $this->context->get('transition_log', []);
        if (!is_array($log)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $item): string => is_string($item) ? $item : 'invalid',
            $log
        ));
    }
}
