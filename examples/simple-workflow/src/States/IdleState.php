<?php

declare(strict_types=1);

namespace AutomataExamples\SimpleWorkflow\States;

use Automata\Contracts\AutomatonInterface;
use Automata\Contracts\ContextInterface;
use Automata\Core\CycleRequest;
use Automata\Core\CycleResponse;
use Automata\Messages\TransitionCommand;
use AutomataExamples\SimpleWorkflow\AdvanceInput;
use AutomataExamples\SimpleWorkflow\Event\WorkflowAdvancedEvent;

final class IdleState implements AutomatonInterface
{
    public const ID = 'workflow.idle';

    public function getId(): string
    {
        return self::ID;
    }

    public function onEnter(ContextInterface $context): void
    {
        $context->set('workflow_status', 'idle');
        $context->set('active_state_label', self::ID);
    }

    public function process(CycleRequest $request): CycleResponse
    {
        $input = $request->getInput();
        if ($input instanceof AdvanceInput) {
            $request->getContext()->set('last_input_reason', $input->getReason());
        }

        return CycleResponse::fromCommand(new TransitionCommand(ActiveState::ID))
            ->withEvent(new WorkflowAdvancedEvent('idle', 'active'));
    }

    public function onLeave(ContextInterface $context): void
    {
    }
}
