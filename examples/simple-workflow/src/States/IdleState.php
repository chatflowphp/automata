<?php

declare(strict_types=1);

namespace AutomataExamples\SimpleWorkflow\States;

use Automata\Context\ContextInterface;
use Automata\Machine\CycleResponse;
use Automata\Machine\InputInterface;
use Automata\State\AbstractState;
use AutomataExamples\SimpleWorkflow\AdvanceInput;
use AutomataExamples\SimpleWorkflow\Event\WorkflowAdvanced;

/**
 * @extends AbstractState<AdvanceInput>
 */
final class IdleState extends AbstractState
{
    public const ID = 'workflow.idle';

    protected const INPUT = AdvanceInput::class;

    public function getId(): string
    {
        return self::ID;
    }

    public function onEnter(ContextInterface $context): CycleResponse
    {
        $context->set('workflow_status', 'idle');

        return CycleResponse::none();
    }

    protected function handle(InputInterface $input, ContextInterface $context): CycleResponse
    {
        $context->set('last_input_reason', $input->reason);

        return CycleResponse::transitionTo(ActiveState::ID)
            ->withEvent(new WorkflowAdvanced('idle', 'active'));
    }
}
