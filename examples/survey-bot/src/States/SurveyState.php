<?php

declare(strict_types=1);

namespace AutomataExamples\SurveyBot\States;

use Automata\Machine\CycleResponse;
use Automata\State\AbstractState;
use AutomataExamples\SurveyBot\Event\BotReply;
use AutomataExamples\SurveyBot\IncomingMessage;

/**
 * @extends AbstractState<IncomingMessage>
 */
abstract class SurveyState extends AbstractState
{
    protected const INPUT = IncomingMessage::class;

    protected function reply(string $text): CycleResponse
    {
        return CycleResponse::fromEvent(new BotReply($text));
    }
}
