<?php

declare(strict_types=1);

namespace Automata\Tests\Examples;

use Automata\Clock\FrozenClock;
use Automata\Snapshot\InMemorySnapshotStore;
use Automata\Snapshot\StateSnapshot;
use AutomataExamples\SurveyBot\States\AskAgeState;
use AutomataExamples\SurveyBot\States\AskNameState;
use AutomataExamples\SurveyBot\States\ConfirmState;
use AutomataExamples\SurveyBot\States\DoneState;
use AutomataExamples\SurveyBot\SurveyBot;
use PHPUnit\Framework\TestCase;

final class SurveyBotTest extends TestCase
{
    public function testConversationSurvivesBetweenRequests(): void
    {
        $store = new InMemorySnapshotStore();
        $clock = FrozenClock::at('2026-09-10T12:00:00+00:00');
        $send = static fn(string $text): array => (new SurveyBot($store, $clock))->handle('42', $text);

        self::assertSame(['Hi! What is your name?'], $send('/start'));
        self::assertSame(AskNameState::ID, self::stateOf($store, 'chat:42'));

        self::assertSame(['Please tell me your name.'], $send('   '));
        self::assertSame(['Nice to meet you, Alice. How old are you?'], $send('Alice'));
        self::assertSame(AskAgeState::ID, self::stateOf($store, 'chat:42'));

        self::assertSame(['Please enter your age as a number between 1 and 120.'], $send('abc'));
        self::assertSame(['Please enter your age as a number between 1 and 120.'], $send('130'));
        self::assertSame(['Alice, 30 years old. Is that correct? (yes/no)'], $send('30'));
        self::assertSame(ConfirmState::ID, self::stateOf($store, 'chat:42'));

        self::assertSame(['Please answer yes or no.'], $send('maybe'));
        self::assertSame(['Thanks, your answers are saved.'], $send('yes'));

        $snapshot = self::snapshotOf($store, 'chat:42');

        self::assertSame(DoneState::ID, $snapshot->currentStateId);
        self::assertSame(['name' => 'Alice', 'age' => 30], $snapshot->contextState);
        self::assertSame(7, $snapshot->tickCount);
        self::assertSame('2026-09-10T12:00:00+00:00', $snapshot->createdAt->format(DATE_ATOM));

        self::assertSame(['The survey is finished. Send /start to begin again.'], $send('hello?'));
        self::assertSame(['Hi! What is your name?'], $send('/start'));
        self::assertSame([], self::snapshotOf($store, 'chat:42')->contextState);
    }

    public function testChatsAreIsolated(): void
    {
        $store = new InMemorySnapshotStore();
        $bot = new SurveyBot($store);

        $bot->handle('1', '/start');
        $bot->handle('1', 'Alice');
        $bot->handle('2', '/start');

        self::assertSame(AskAgeState::ID, self::stateOf($store, 'chat:1'));
        self::assertSame(AskNameState::ID, self::stateOf($store, 'chat:2'));
        self::assertSame(['Please enter your age as a number between 1 and 120.'], $bot->handle('1', 'not a number'));
    }

    public function testConfirmNoRestartsTheSurvey(): void
    {
        $store = new InMemorySnapshotStore();
        $bot = new SurveyBot($store);

        $bot->handle('7', '/start');
        $bot->handle('7', 'Alice');
        $bot->handle('7', '30');

        self::assertSame(['Hi! What is your name?'], $bot->handle('7', 'no'));
        self::assertSame(AskNameState::ID, self::stateOf($store, 'chat:7'));
    }

    private static function stateOf(InMemorySnapshotStore $store, string $key): ?string
    {
        return $store->load($key)?->currentStateId;
    }

    private static function snapshotOf(InMemorySnapshotStore $store, string $key): StateSnapshot
    {
        $snapshot = $store->load($key);

        self::assertNotNull($snapshot);

        return $snapshot;
    }
}
