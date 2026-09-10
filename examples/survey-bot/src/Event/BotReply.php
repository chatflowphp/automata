<?php

declare(strict_types=1);

namespace AutomataExamples\SurveyBot\Event;

use Automata\Messaging\EventInterface;

/**
 * Something the bot wants to say. The transport layer sends it after the tick commits.
 */
final class BotReply implements EventInterface
{
    public function __construct(public readonly string $text) {}
}
