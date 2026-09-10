<?php

declare(strict_types=1);

namespace Automata\Tests\Snapshot;

use Automata\Context\ArrayContext;
use Automata\Context\ContextInterface;
use Automata\Exception\IllegalStateException;
use Automata\Machine\CycleRequest;
use Automata\Machine\CycleResponse;
use Automata\Machine\StateMachine;
use Automata\Snapshot\InMemorySnapshotStore;
use Automata\Snapshot\Session;
use Automata\Tests\Support\MessageLog;
use Automata\Tests\Support\RecordingState;
use Automata\Tests\Support\TestEvent;
use Automata\Tests\Support\TestInput;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    public function testResumeStartsFreshMachineThenRestoresPersistedOne(): void
    {
        $store = new InMemorySnapshotStore();
        $log = new MessageLog();
        $factory = static function () use ($log): StateMachine {
            $machine = new StateMachine(new ArrayContext());
            $machine->registerStates(
                new RecordingState(
                    'first',
                    static function (CycleRequest $request): CycleResponse {
                        $request->getContext()->set('answer', $request->getInput() instanceof TestInput ? $request->getInput()->text : '');

                        return CycleResponse::transitionTo('second');
                    },
                    static fn(ContextInterface $context): CycleResponse => CycleResponse::fromEvent(new TestEvent('ask')),
                ),
                new RecordingState('second'),
            );
            $machine->subscribe(TestEvent::class, $log);

            return $machine;
        };

        $session = Session::resume($store, 'chat:1', $factory, 'first');

        self::assertTrue($session->isNew());
        self::assertSame('chat:1', $session->getKey());
        self::assertSame('first', $session->getMachine()->getCurrentStateId());
        self::assertSame(['event:ask'], $log->entries);

        $session->persist();
        self::assertSame(['chat:1'], $store->keys());

        $resumed = Session::resume($store, 'chat:1', $factory, 'first');

        self::assertFalse($resumed->isNew());
        self::assertSame('first', $resumed->getMachine()->getCurrentStateId());
        self::assertSame(['event:ask'], $log->entries, 'restore must not re-run onEnter');

        $result = $resumed->tick(new TestInput('42'));
        $resumed->persist();

        $saved = $store->load('chat:1');

        self::assertTrue($result->transitioned());
        self::assertNotNull($saved);
        self::assertSame('second', $saved->currentStateId);
        self::assertSame('42', $saved->contextState['answer']);
        self::assertSame(1, $saved->tickCount);

        $resumed->end();
        self::assertNull($store->load('chat:1'));
    }

    public function testFactoryMustReturnUnstartedMachine(): void
    {
        $factory = static function (): StateMachine {
            $machine = new StateMachine(new ArrayContext());
            $machine->registerState(new RecordingState('a'));
            $machine->start('a');

            return $machine;
        };

        $this->expectException(IllegalStateException::class);
        $this->expectExceptionMessage('Session factory must return a state machine that is not started.');

        Session::resume(new InMemorySnapshotStore(), 'k', $factory, 'a');
    }
}
