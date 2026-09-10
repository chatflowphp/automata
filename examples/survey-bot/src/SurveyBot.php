<?php

declare(strict_types=1);

namespace AutomataExamples\SurveyBot;

use Automata\Context\ArrayContext;
use Automata\Context\ContextInterface;
use Automata\Machine\StateMachine;
use Automata\Machine\Transition\TransitionTable;
use Automata\Snapshot\Session;
use Automata\Snapshot\SnapshotStoreInterface;
use AutomataExamples\SurveyBot\Event\BotReply;
use AutomataExamples\SurveyBot\States\AskAgeState;
use AutomataExamples\SurveyBot\States\AskNameState;
use AutomataExamples\SurveyBot\States\ConfirmState;
use AutomataExamples\SurveyBot\States\DoneState;
use Psr\Clock\ClockInterface;

/**
 * Webhook-style entry point: every call builds a fresh machine, resumes the chat from the store,
 * applies one message, persists, and returns what the bot replied.
 */
final class SurveyBot
{
    public function __construct(
        private readonly SnapshotStoreInterface $store,
        private readonly ?ClockInterface $clock = null,
    ) {}

    /**
     * @return list<string>
     */
    public function handle(string $chatId, string $text): array
    {
        $replies = new ReplyCollector();
        $session = Session::resume(
            $this->store,
            'chat:' . $chatId,
            fn(): StateMachine => $this->buildMachine($replies),
            AskNameState::ID,
        );

        if (!$session->isNew()) {
            $session->tick(new IncomingMessage($text));
        }

        $session->persist();

        return $replies->all();
    }

    public static function transitions(): TransitionTable
    {
        return TransitionTable::define([
            AskNameState::ID => [AskAgeState::ID => static fn(ContextInterface $c): bool => $c->getString('name') !== ''],
            AskAgeState::ID => [ConfirmState::ID => static fn(ContextInterface $c): bool => $c->getInt('age') > 0],
            ConfirmState::ID => [DoneState::ID, AskNameState::ID],
            DoneState::ID => [AskNameState::ID],
        ]);
    }

    private function buildMachine(ReplyCollector $replies): StateMachine
    {
        $machine = new StateMachine(new ArrayContext(), transitions: self::transitions(), clock: $this->clock);
        $machine->registerStates(new AskNameState(), new AskAgeState(), new ConfirmState(), new DoneState());
        $machine->subscribe(BotReply::class, $replies);

        return $machine;
    }
}
