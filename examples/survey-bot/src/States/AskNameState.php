<?php

declare(strict_types=1);

namespace AutomataExamples\SurveyBot\States;

use Automata\Context\ContextInterface;
use Automata\Machine\CycleResponse;
use Automata\Machine\InputInterface;

final class AskNameState extends SurveyState
{
    public const ID = 'survey.ask_name';

    public function getId(): string
    {
        return self::ID;
    }

    public function onEnter(ContextInterface $context): CycleResponse
    {
        return $this->reply('Hi! What is your name?');
    }

    protected function handle(InputInterface $input, ContextInterface $context): CycleResponse
    {
        $name = $input->trimmed();

        if ($name === '') {
            return $this->reply('Please tell me your name.');
        }

        $context->set('name', $name);

        return CycleResponse::transitionTo(AskAgeState::ID);
    }
}
