<?php

declare(strict_types=1);

namespace AutomataExamples\SurveyBot\States;

use Automata\Context\ContextInterface;
use Automata\Machine\CycleResponse;
use Automata\Machine\InputInterface;

final class DoneState extends SurveyState
{
    public const ID = 'survey.done';

    public function getId(): string
    {
        return self::ID;
    }

    public function onEnter(ContextInterface $context): CycleResponse
    {
        return $this->reply('Thanks, your answers are saved.');
    }

    protected function handle(InputInterface $input, ContextInterface $context): CycleResponse
    {
        if ($input->trimmed() === '/start') {
            $context->remove('name');
            $context->remove('age');

            return CycleResponse::transitionTo(AskNameState::ID);
        }

        return $this->reply('The survey is finished. Send /start to begin again.');
    }
}
