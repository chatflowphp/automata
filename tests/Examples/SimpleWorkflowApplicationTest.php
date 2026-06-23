<?php

declare(strict_types=1);

namespace Automata\Tests\Examples;

use AutomataExamples\SimpleWorkflow\AdvanceInput;
use AutomataExamples\SimpleWorkflow\Application\SimpleWorkflowApplication;
use AutomataExamples\SimpleWorkflow\States\ActiveState;
use AutomataExamples\SimpleWorkflow\States\IdleState;
use PHPUnit\Framework\TestCase;

final class SimpleWorkflowApplicationTest extends TestCase
{
    public function testSimpleWorkflowExampleCoversActivationTransitionEventAndSnapshotRestore(): void
    {
        $application = new SimpleWorkflowApplication();
        $orchestrator = $application->getOrchestrator();
        $context = $application->getContext();

        self::assertTrue($orchestrator->hasAutomaton(IdleState::ID));
        self::assertTrue($orchestrator->hasAutomaton(ActiveState::ID));
        self::assertFalse($orchestrator->hasActiveAutomaton());

        $orchestrator->activate(IdleState::ID);

        self::assertTrue($orchestrator->hasActiveAutomaton());
        self::assertSame(IdleState::ID, $orchestrator->getActiveAutomatonId());
        self::assertSame('idle', $application->workflowStatus());
        self::assertSame(0, $application->cycleCount());
        self::assertSame([], $application->transitionLog());

        $orchestrator->tick(new AdvanceInput('activate-workflow'));

        self::assertSame(ActiveState::ID, $orchestrator->getActiveAutomatonId());
        self::assertSame('active', $application->workflowStatus());
        self::assertSame(1, $application->cycleCount());
        self::assertSame('activate-workflow', $context->get('last_input_reason'));
        self::assertSame(
            ['idle->active|active=workflow.active|cycles=1'],
            $application->transitionLog()
        );

        $snapshot = $orchestrator->snapshot();

        self::assertSame(
            [
                'contextState' => [
                    'cycle_count' => 1,
                    'workflow_status' => 'active',
                    'transition_log' => ['idle->active|active=workflow.active|cycles=1'],
                    'active_state_label' => 'workflow.active',
                    'last_input_reason' => 'activate-workflow',
                ],
                'fsmAutomatonId' => ActiveState::ID,
                'automataStates' => [],
            ],
            $snapshot->toArray()
        );

        $restoredApplication = new SimpleWorkflowApplication();
        $restoredOrchestrator = $restoredApplication->getOrchestrator();
        $restoredOrchestrator->activateFromSnapshot($snapshot);

        self::assertSame(ActiveState::ID, $restoredOrchestrator->getActiveAutomatonId());
        self::assertSame('active', $restoredApplication->workflowStatus());
        self::assertSame(1, $restoredApplication->cycleCount());
        self::assertSame(
            ['idle->active|active=workflow.active|cycles=1'],
            $restoredApplication->transitionLog()
        );
        self::assertSame('activate-workflow', $restoredApplication->getContext()->get('last_input_reason'));
    }
}
