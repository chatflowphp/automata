<?php

declare(strict_types=1);

namespace AutomataExamples\SimpleWorkflow\States;

use Automata\Contracts\AutomatonInterface;
use Automata\Contracts\ContextInterface;
use Automata\Core\CycleRequest;
use Automata\Core\CycleResponse;
use AutomataExamples\SimpleWorkflow\AdvanceInput;

final class ActiveState implements AutomatonInterface
{
    public const ID = 'workflow.active';

    public function getId(): string
    {
        return self::ID;
    }

    public function onEnter(ContextInterface $context): void
    {
        $context->set('workflow_status', 'active');
        $context->set('active_state_label', self::ID);
    }

    public function process(CycleRequest $request): CycleResponse
    {
        $input = $request->getInput();
        if ($input instanceof AdvanceInput) {
            $request->getContext()->set('last_input_reason', $input->getReason());
        }

        return CycleResponse::none();
    }

    public function onLeave(ContextInterface $context): void
    {
    }
}
