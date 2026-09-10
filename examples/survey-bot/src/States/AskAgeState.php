<?php

declare(strict_types=1);

namespace AutomataExamples\SurveyBot\States;

use Automata\Context\ContextInterface;
use Automata\Machine\CycleResponse;
use Automata\Machine\InputInterface;

final class AskAgeState extends SurveyState
{
    public const ID = 'survey.ask_age';

    public function getId(): string
    {
        return self::ID;
    }

    public function onEnter(ContextInterface $context): CycleResponse
    {
        return $this->reply(\sprintf('Nice to meet you, %s. How old are you?', $context->getString('name')));
    }

    protected function handle(InputInterface $input, ContextInterface $context): CycleResponse
    {
        $text = $input->trimmed();

        if (!ctype_digit($text) || (int) $text < 1 || (int) $text > 120) {
            return $this->reply('Please enter your age as a number between 1 and 120.');
        }

        $context->set('age', (int) $text);

        return CycleResponse::transitionTo(ConfirmState::ID);
    }
}
