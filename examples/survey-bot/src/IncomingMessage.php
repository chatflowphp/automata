<?php

declare(strict_types=1);

namespace AutomataExamples\SurveyBot;

use Automata\Machine\InputInterface;

/**
 * One message from the user, as delivered by a webhook.
 */
final class IncomingMessage implements InputInterface
{
    public function __construct(public readonly string $text) {}

    public function trimmed(): string
    {
        return trim($this->text);
    }
}
