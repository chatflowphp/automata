<?php

declare(strict_types=1);

namespace Automata\Tests\Support;

use Automata\Events\StateActivated;
use Automata\Events\TickCompleted;
use Automata\Events\TransitionApplied;
use Automata\Messaging\TransitionCommandInterface;

/**
 * Invokable listener that records a short label for every message it receives.
 */
final class MessageLog
{
    /**
     * @var list<string>
     */
    public array $entries = [];

    /**
     * @var list<object>
     */
    public array $messages = [];

    public function __invoke(object $message): void
    {
        $this->messages[] = $message;
        $this->entries[] = self::describe($message);
    }

    public static function describe(object $message): string
    {
        return match (true) {
            $message instanceof StateActivated => \sprintf('activated:%s<-%s', $message->stateId, $message->previousStateId ?? 'none'),
            $message instanceof TransitionApplied => \sprintf('transition:%s->%s', $message->fromStateId, $message->toStateId),
            $message instanceof TickCompleted => \sprintf('completed:%d', $message->result->tickNumber),
            $message instanceof TransitionCommandInterface => \sprintf('transition-command:%s', $message->getTargetStateId()),
            $message instanceof TestEvent => 'event:' . $message->label,
            $message instanceof TestCommand => 'command:' . $message->label,
            default => $message::class,
        };
    }
}
