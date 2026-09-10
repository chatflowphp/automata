<?php

declare(strict_types=1);

namespace AutomataExamples\SurveyBot;

use AutomataExamples\SurveyBot\Event\BotReply;

final class ReplyCollector
{
    /**
     * @var list<string>
     */
    private array $replies = [];

    public function __invoke(BotReply $reply): void
    {
        $this->replies[] = $reply->text;
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->replies;
    }
}
