<?php

declare(strict_types=1);

namespace Automata\Tests\Examples;

use Automata\Clock\FrozenClock;
use Automata\Snapshot\StateSnapshot;
use AutomataExamples\SimpleWorkflow\AdvanceInput;
use AutomataExamples\SimpleWorkflow\Application\SimpleWorkflowApplication;
use AutomataExamples\SimpleWorkflow\States\ActiveState;
use AutomataExamples\SimpleWorkflow\States\IdleState;
use PHPUnit\Framework\TestCase;

final class SimpleWorkflowApplicationTest extends TestCase
{
    public function testWorkflowStartsTransitionsAndRestores(): void
    {
        $clock = FrozenClock::at('2026-09-10T12:00:00+00:00');
        $application = new SimpleWorkflowApplication($clock);
        $machine = $application->getMachine();
        $context = $application->getContext();

        self::assertTrue($machine->hasState(IdleState::ID));
        self::assertTrue($machine->hasState(ActiveState::ID));
        self::assertFalse($machine->isStarted());

        $machine->start(IdleState::ID);

        self::assertSame(IdleState::ID, $machine->getCurrentStateId());
        self::assertSame('idle', $application->workflowStatus());
        self::assertSame(0, $application->cycleCount());
        self::assertSame([], $application->transitionLog());

        $result = $machine->tick(new AdvanceInput('activate-workflow'));

        self::assertTrue($result->transitioned());
        self::assertSame(ActiveState::ID, $machine->getCurrentStateId());
        self::assertSame('active', $application->workflowStatus());
        self::assertSame(1, $application->cycleCount());
        self::assertSame('activate-workflow', $context->getString('last_input_reason'));
        self::assertSame(['idle->active|current=workflow.active|cycles=1'], $application->transitionLog());

        $snapshot = $machine->snapshot();

        self::assertSame([
            'schemaVersion' => StateSnapshot::SCHEMA_VERSION,
            'createdAt' => '2026-09-10T12:00:00+00:00',
            'tickCount' => 1,
            'currentStateId' => ActiveState::ID,
            'contextState' => [
                'cycle_count' => 1,
                'workflow_status' => 'active',
                'last_input_reason' => 'activate-workflow',
                'transition_log' => ['idle->active|current=workflow.active|cycles=1'],
            ],
            'stateData' => [],
        ], $snapshot->toArray());

        $restored = new SimpleWorkflowApplication($clock);
        $restored->getMachine()->restore($snapshot);

        self::assertSame(ActiveState::ID, $restored->getMachine()->getCurrentStateId());
        self::assertSame('active', $restored->workflowStatus());
        self::assertSame(1, $restored->cycleCount());
        self::assertSame(['idle->active|current=workflow.active|cycles=1'], $restored->transitionLog());

        $next = $restored->getMachine()->tick(new AdvanceInput('again'));

        self::assertFalse($next->transitioned());
        self::assertSame(2, $next->tickNumber);
        self::assertSame('again', $restored->getContext()->getString('last_input_reason'));
    }
}
