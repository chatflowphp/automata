<?php

declare(strict_types=1);

namespace AutomataExamples\SurveyBot\States;

use Automata\Context\ContextInterface;
use Automata\Machine\CycleResponse;
use Automata\Machine\InputInterface;

final class ConfirmState extends SurveyState
{
    public const ID = 'survey.confirm';

    public function getId(): string
    {
        return self::ID;
    }

    public function onEnter(ContextInterface $context): CycleResponse
    {
        return $this->reply(\sprintf(
            '%s, %d years old. Is that correct? (yes/no)',
            $context->getString('name'),
            $context->getInt('age'),
        ));
    }

    protected function handle(InputInterface $input, ContextInterface $context): CycleResponse
    {
        return match (strtolower($input->trimmed())) {
            'yes', 'y' => CycleResponse::transitionTo(DoneState::ID),
            'no', 'n' => CycleResponse::transitionTo(AskNameState::ID),
            default => $this->reply('Please answer yes or no.'),
        };
    }
}
