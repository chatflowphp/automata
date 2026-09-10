<?php

declare(strict_types=1);

namespace Automata\Tests\Machine;

use Automata\Clock\FrozenClock;
use Automata\Context\ArrayContext;
use Automata\Context\ContextInterface;
use Automata\Events\StateActivated;
use Automata\Events\TickCompleted;
use Automata\Events\TransitionApplied;
use Automata\Exception\DuplicateStateException;
use Automata\Exception\IllegalStateException;
use Automata\Exception\InvalidTransitionException;
use Automata\Exception\ReentrantTickException;
use Automata\Exception\SnapshotHydrationException;
use Automata\Exception\StateNotFoundException;
use Automata\Machine\CycleRequest;
use Automata\Machine\CycleResponse;
use Automata\Machine\StateMachine;
use Automata\Machine\TickResult;
use Automata\Machine\Transition\TransitionTable;
use Automata\Messaging\EventInterface;
use Automata\Messaging\TransitionCommand;
use Automata\Snapshot\StateSnapshot;
use Automata\Tests\Support\CallbackMiddleware;
use Automata\Tests\Support\CustomTransitionCommand;
use Automata\Tests\Support\MessageLog;
use Automata\Tests\Support\OtherInput;
use Automata\Tests\Support\RecordingState;
use Automata\Tests\Support\ResumableRecordingState;
use Automata\Tests\Support\SerializableRecordingState;
use Automata\Tests\Support\TestCommand;
use Automata\Tests\Support\TestEvent;
use Automata\Tests\Support\TestInput;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StateMachineTest extends TestCase
{
    // -- registration and introspection ---------------------------------------------------------

    public function testIntrospectionBeforeAndAfterStart(): void
    {
        $machine = new StateMachine(new ArrayContext());
        $a = new RecordingState('a');

        self::assertFalse($machine->isStarted());
        self::assertNull($machine->getCurrentStateId());
        self::assertFalse($machine->hasState('a'));
        self::assertSame([], $machine->getStateIds());
        self::assertSame(0, $machine->getTickCount());
        self::assertFalse($machine->canTransitionTo('a'));
        self::assertSame([], $machine->getAllowedTransitions());

        $machine->registerStates($a, new RecordingState('b'));
        $machine->start('a');

        self::assertTrue($machine->isStarted());
        self::assertSame('a', $machine->getCurrentStateId());
        self::assertSame(['a', 'b'], $machine->getStateIds());
        self::assertSame(1, $a->enterCount);
        self::assertTrue($machine->canTransitionTo('a'));
        self::assertTrue($machine->canTransitionTo('b'));
        self::assertFalse($machine->canTransitionTo('missing'));
        self::assertSame(['b'], $machine->getAllowedTransitions());
    }

    public function testDuplicateStateIdIsRejected(): void
    {
        $machine = new StateMachine(new ArrayContext());
        $machine->registerState(new RecordingState('a'));

        $this->expectException(DuplicateStateException::class);

        $machine->registerState(new RecordingState('a'));
    }

    // -- lifecycle guards -----------------------------------------------------------------------

    public function testStartUnknownStateThrows(): void
    {
        $machine = new StateMachine(new ArrayContext());

        $this->expectException(StateNotFoundException::class);
        $this->expectExceptionMessage('State "missing" is not registered.');

        $machine->start('missing');
    }

    public function testStartTwiceThrows(): void
    {
        $machine = new StateMachine(new ArrayContext());
        $machine->registerStates(new RecordingState('a'), new RecordingState('b'));
        $machine->start('a');

        $this->expectException(IllegalStateException::class);
        $this->expectExceptionMessage('State machine is already started in state "a".');

        $machine->start('b');
    }

    public function testTickBeforeStartThrows(): void
    {
        $machine = new StateMachine(new ArrayContext());

        $this->expectException(IllegalStateException::class);
        $this->expectExceptionMessage('Cannot tick: state machine is not started. Call start() or restore() first.');

        $machine->tick(new TestInput());
    }

    public function testTransitionToBeforeStartThrows(): void
    {
        $machine = new StateMachine(new ArrayContext());

        $this->expectException(IllegalStateException::class);
        $this->expectExceptionMessage('Cannot transition: state machine is not started.');

        $machine->transitionTo('a');
    }

    // -- start ----------------------------------------------------------------------------------

    public function testStartRunsOnEnterAndDispatchesItsMessages(): void
    {
        $context = new ArrayContext();
        $machine = new StateMachine($context);
        $log = new MessageLog();
        $state = new RecordingState('a', null, static function (ContextInterface $context): CycleResponse {
            $context->set('entered', true);

            return CycleResponse::fromEvent(new TestEvent('welcome'));
        });

        $machine->registerState($state);
        $machine->subscribe(StateActivated::class, $log);
        $machine->subscribe(TestEvent::class, $log);

        $messages = $machine->start('a');

        self::assertSame(['activated:a<-none', 'event:welcome'], $log->entries);
        self::assertSame(['activated:a<-none', 'event:welcome'], array_map([MessageLog::class, 'describe'], $messages));
        self::assertTrue($context->getBool('entered'));
    }

    public function testStartRollsBackWhenOnEnterFails(): void
    {
        $context = new ArrayContext(['untouched' => true]);
        $machine = new StateMachine($context);
        $log = new MessageLog();
        $machine->registerState(new RecordingState('a', null, static function (ContextInterface $context): CycleResponse {
            $context->set('entered', true);

            throw new RuntimeException('boom');
        }));
        $machine->subscribe(EventInterface::class, $log);

        try {
            $machine->start('a');
            self::fail('Expected exception');
        } catch (RuntimeException $exception) {
            self::assertSame('boom', $exception->getMessage());
        }

        self::assertFalse($machine->isStarted());
        self::assertSame(['untouched' => true], $context->getState());
        self::assertSame([], $log->entries);
    }

    // -- middleware -----------------------------------------------------------------------------

    public function testMiddlewareWrapsTickInRegistrationOrderAndSeesResult(): void
    {
        $context = new ArrayContext();
        $machine = new StateMachine($context);
        $seenResult = null;

        $outer = new CallbackMiddleware(static function (CycleRequest $request, callable $next): TickResult {
            $request->getContext()->push('trace', 'outer.before');
            $result = $next($request);
            $request->getContext()->push('trace', 'outer.after');

            return $result;
        });
        $inner = new CallbackMiddleware(static function (CycleRequest $request, callable $next) use (&$seenResult): TickResult {
            $request->getContext()->push('trace', 'inner.before');
            $result = $next($request->withInput(new TestInput('rewritten')));
            $request->getContext()->push('trace', 'inner.after');
            $seenResult = $result;

            return $result;
        });

        $machine->registerMiddleware($outer);
        $machine->registerMiddleware($inner);
        $machine->registerState(new RecordingState('a', static function (CycleRequest $request): CycleResponse {
            $input = $request->getInput();
            $request->getContext()->push('trace', 'process:' . ($input instanceof TestInput ? $input->text : '?'));

            return CycleResponse::none();
        }));
        $machine->start('a');

        $result = $machine->tick(new TestInput('original'));

        self::assertSame(
            ['outer.before', 'inner.before', 'process:rewritten', 'inner.after', 'outer.after'],
            $context->getList('trace'),
        );
        self::assertSame($result, $seenResult);
    }

    public function testMiddlewareCanSnapshotTheCommittedTick(): void
    {
        $context = new ArrayContext();
        $machine = new StateMachine($context);
        $captured = null;
        $persist = new CallbackMiddleware(static function (CycleRequest $request, callable $next) use ($machine, &$captured): TickResult {
            $result = $next($request);
            $captured = $machine->snapshot();

            return $result;
        });

        $machine->registerMiddleware($persist);
        $machine->registerStates(
            new RecordingState('a', static fn(): CycleResponse => CycleResponse::transitionTo('b')),
            new RecordingState('b'),
        );
        $machine->start('a');
        $machine->tick(new TestInput());

        self::assertInstanceOf(StateSnapshot::class, $captured);
        self::assertSame('b', $captured->currentStateId);
        self::assertSame(1, $captured->tickCount);
    }

    public function testMiddlewareExceptionAfterNextRollsBackTheTick(): void
    {
        $context = new ArrayContext();
        $machine = new StateMachine($context);
        $log = new MessageLog();
        $veto = new CallbackMiddleware(static function (CycleRequest $request, callable $next): TickResult {
            $next($request);

            throw new RuntimeException('persist failed');
        });
        $a = new RecordingState('a', static function (CycleRequest $request): CycleResponse {
            $request->getContext()->set('touched', true);

            return CycleResponse::transitionTo('b')->withEvent(new TestEvent('domain'));
        });

        $machine->registerMiddleware($veto);
        $machine->registerStates($a, new RecordingState('b'));
        $machine->subscribe(EventInterface::class, $log);
        $machine->start('a');
        $log->entries = [];

        try {
            $machine->tick(new TestInput());
            self::fail('Expected exception');
        } catch (RuntimeException $exception) {
            self::assertSame('persist failed', $exception->getMessage());
        }

        self::assertSame('a', $machine->getCurrentStateId());
        self::assertSame(0, $machine->getTickCount());
        self::assertFalse($context->has('touched'));
        self::assertSame([], $log->entries);
    }

    // -- ticks and transitions ------------------------------------------------------------------

    public function testTransitionOrderingHooksAndListenersObserveCommittedState(): void
    {
        $context = new ArrayContext();
        $machine = new StateMachine($context);
        $log = [];

        $source = new RecordingState(
            'source',
            static fn(): CycleResponse => CycleResponse::fromCommand(new CustomTransitionCommand('target'))
                ->withCommand(new TestCommand('plain'))
                ->withEvent(new TestEvent('domain')),
            static function (ContextInterface $context): CycleResponse {
                $context->set('color', 'red');

                return CycleResponse::none();
            },
            static function (ContextInterface $context): void {
                $context->set('left', 'source');
            },
        );
        $target = new RecordingState('target', null, static function (ContextInterface $context): CycleResponse {
            $context->set('color', 'green');

            return CycleResponse::fromEvent(new TestEvent('entered-target'));
        });

        $machine->registerStates($source, $target);
        $record = static function (object $message) use (&$log, $machine, $context): void {
            $log[] = \sprintf(
                '%s @%s/%s',
                MessageLog::describe($message),
                $machine->getCurrentStateId() ?? 'none',
                $context->getString('color', 'none'),
            );
        };
        $machine->subscribe(TransitionApplied::class, $record);
        $machine->subscribe(StateActivated::class, $record);
        $machine->subscribe(CustomTransitionCommand::class, $record);
        $machine->subscribe(TestCommand::class, $record);
        $machine->subscribe(TestEvent::class, $record);
        $machine->subscribe(TickCompleted::class, $record);

        $machine->start('source');
        $result = $machine->tick(new TestInput());

        self::assertSame([
            'activated:source<-none @source/red',
            'transition:source->target @target/green',
            'activated:target<-source @target/green',
            'event:entered-target @target/green',
            'transition-command:target @target/green',
            'command:plain @target/green',
            'event:domain @target/green',
            'completed:1 @target/green',
        ], $log);

        self::assertSame(1, $source->leaveCount);
        self::assertSame(1, $target->enterCount);
        self::assertSame('source', $context->getString('left'));
        self::assertSame('source', $result->fromStateId);
        self::assertSame('target', $result->toStateId);
        self::assertTrue($result->transitioned());
        self::assertSame(1, $result->tickNumber);
        self::assertCount(6, $result->messages);
        self::assertCount(2, $result->messagesOf(TestEvent::class));
        self::assertSame(1, $machine->getTickCount());
    }

    public function testSelfTransitionIsNoOp(): void
    {
        $machine = new StateMachine(new ArrayContext());
        $log = new MessageLog();
        $self = new RecordingState('self', static fn(): CycleResponse => CycleResponse::transitionTo('self'));

        $machine->registerState($self);
        $machine->subscribe(EventInterface::class, $log);
        $machine->start('self');
        $log->entries = [];

        $result = $machine->tick(new TestInput());

        self::assertFalse($result->transitioned());
        self::assertSame(1, $self->enterCount);
        self::assertSame(0, $self->leaveCount);
        self::assertSame(['completed:1'], $log->entries);
        self::assertSame([], $machine->transitionTo('self'));
    }

    public function testOnEnterCanChainTransitions(): void
    {
        $machine = new StateMachine(new ArrayContext());
        $log = new MessageLog();
        $a = new RecordingState('a', static fn(): CycleResponse => CycleResponse::transitionTo('b'));
        $b = new RecordingState('b', null, static fn(): CycleResponse => CycleResponse::transitionTo('c'));
        $c = new RecordingState('c', null, static fn(): CycleResponse => CycleResponse::fromEvent(new TestEvent('arrived')));

        $machine->registerStates($a, $b, $c);
        $machine->subscribe(EventInterface::class, $log);
        $machine->start('a');
        $log->entries = [];

        $result = $machine->tick(new TestInput());

        self::assertSame('c', $result->toStateId);
        self::assertSame([
            'transition:a->b',
            'activated:b<-a',
            'transition:b->c',
            'activated:c<-b',
            'event:arrived',
            'completed:1',
        ], $log->entries);
        self::assertSame([1, 1], [$b->enterCount, $b->leaveCount]);
        self::assertSame([1, 0], [$c->enterCount, $c->leaveCount]);
    }

    public function testMultipleTransitionCommandsInOneResponseAreAppliedInOrder(): void
    {
        $machine = new StateMachine(new ArrayContext());
        $b = new RecordingState('b');
        $c = new RecordingState('c');
        $machine->registerStates(
            new RecordingState('a', static fn(): CycleResponse => CycleResponse::fromCommands([
                new TransitionCommand('b'),
                new TransitionCommand('c'),
            ])),
            $b,
            $c,
        );
        $machine->start('a');

        $result = $machine->tick(new TestInput());

        self::assertSame('c', $result->toStateId);
        self::assertSame([1, 1, 1], [$b->enterCount, $b->leaveCount, $c->enterCount]);
    }

    public function testTransitionLoopIsCutAndRolledBack(): void
    {
        $context = new ArrayContext();
        $machine = new StateMachine($context);
        $machine->registerStates(
            new RecordingState('a', static fn(): CycleResponse => CycleResponse::transitionTo('b')),
            new RecordingState('b', null, static function (ContextInterface $context): CycleResponse {
                $context->increment('bounces');

                return CycleResponse::transitionTo('c');
            }),
            new RecordingState('c', null, static fn(): CycleResponse => CycleResponse::transitionTo('b')),
        );
        $machine->start('a');

        try {
            $machine->tick(new TestInput());
            self::fail('Expected exception');
        } catch (InvalidTransitionException $exception) {
            self::assertStringContainsString('More than 32 transitions', $exception->getMessage());
        }

        self::assertSame('a', $machine->getCurrentStateId());
        self::assertFalse($context->has('bounces'));
        self::assertSame(0, $machine->getTickCount());
    }

    public function testTransitionToUnknownStateThrowsAndRollsBack(): void
    {
        $context = new ArrayContext();
        $machine = new StateMachine($context);
        $machine->registerState(new RecordingState('a', static function (CycleRequest $request): CycleResponse {
            $request->getContext()->set('dirty', true);

            return CycleResponse::transitionTo('missing');
        }));
        $machine->start('a');

        try {
            $machine->tick(new TestInput());
            self::fail('Expected exception');
        } catch (StateNotFoundException $exception) {
            self::assertSame('State "missing" is not registered.', $exception->getMessage());
        }

        self::assertFalse($context->has('dirty'));
    }

    public function testTransitionPolicyIsEnforcedWithGuards(): void
    {
        $context = new ArrayContext();
        $table = TransitionTable::define([
            'a' => ['b' => static fn(ContextInterface $context): bool => $context->getBool('ready')],
            'b' => ['a'],
        ]);
        $machine = new StateMachine($context, transitions: $table);
        $a = new RecordingState('a', static fn(): CycleResponse => CycleResponse::transitionTo('b'));
        $machine->registerStates($a, new RecordingState('b'), new RecordingState('c'));
        $machine->start('a');

        self::assertFalse($machine->canTransitionTo('b'));
        self::assertSame([], $machine->getAllowedTransitions());
        self::assertSame($table, $machine->getTransitions());

        try {
            $machine->tick(new TestInput());
            self::fail('Expected exception');
        } catch (InvalidTransitionException $exception) {
            self::assertSame('Transition from "a" to "b" is not allowed.', $exception->getMessage());
        }

        self::assertSame('a', $machine->getCurrentStateId());
        self::assertSame(0, $a->leaveCount);

        $context->set('ready', true);

        self::assertTrue($machine->canTransitionTo('b'));
        self::assertSame(['b'], $machine->getAllowedTransitions());
        self::assertSame('b', $machine->tick(new TestInput())->toStateId);
        self::assertFalse($machine->canTransitionTo('c'));
    }

    // -- atomicity ------------------------------------------------------------------------------

    public function testProcessExceptionRollsBackContextStateDataAndTickCount(): void
    {
        $context = new ArrayContext(['n' => 1]);
        $machine = new StateMachine($context);
        $log = new MessageLog();
        $state = null;
        $state = new SerializableRecordingState('a', static function (CycleRequest $request) use (&$state): CycleResponse {
            $request->getContext()->set('n', 2);

            if ($state instanceof SerializableRecordingState) {
                $state->state = ['inner' => 'during'];
            }

            throw new RuntimeException('process failed');
        });
        $state->state = ['inner' => 'before'];

        $machine->registerState($state);
        $machine->subscribe(EventInterface::class, $log);
        $machine->start('a');
        $log->entries = [];

        try {
            $machine->tick(new TestInput());
            self::fail('Expected exception');
        } catch (RuntimeException $exception) {
            self::assertSame('process failed', $exception->getMessage());
        }

        self::assertSame(1, $context->getInt('n'));
        self::assertSame(0, $machine->getTickCount());
        self::assertSame(['inner' => 'before'], $state->state);
        self::assertSame([], $log->entries);
    }

    public function testOnEnterExceptionMidTransitionRestoresSourceState(): void
    {
        $context = new ArrayContext();
        $machine = new StateMachine($context);
        $source = new RecordingState('source', static function (CycleRequest $request): CycleResponse {
            $input = $request->getInput();

            if ($input instanceof TestInput && $input->text === 'retry') {
                return CycleResponse::none();
            }

            return CycleResponse::transitionTo('broken');
        });
        $broken = new RecordingState('broken', null, static function (): CycleResponse {
            throw new RuntimeException('cannot enter');
        });

        $machine->registerStates($source, $broken);
        $machine->start('source');

        try {
            $machine->tick(new TestInput());
            self::fail('Expected exception');
        } catch (RuntimeException $exception) {
            self::assertSame('cannot enter', $exception->getMessage());
        }

        self::assertSame('source', $machine->getCurrentStateId());
        self::assertSame(1, $source->leaveCount, 'hooks that already ran are not undone');
        self::assertSame(1, $broken->enterCount);

        self::assertSame(1, $machine->tick(new TestInput('retry'))->tickNumber);
    }

    public function testReentrantTickFromStateIsRejectedAndRolledBack(): void
    {
        $context = new ArrayContext();
        $machine = new StateMachine($context);
        $machine->registerState(new RecordingState('a', static function (CycleRequest $request) use ($machine): CycleResponse {
            $input = $request->getInput();

            if ($input instanceof TestInput && $input->text === 'retry') {
                return CycleResponse::none();
            }

            $request->getContext()->set('dirty', true);
            $machine->tick(new TestInput('nested'));

            return CycleResponse::none();
        }));
        $machine->start('a');

        try {
            $machine->tick(new TestInput());
            self::fail('Expected exception');
        } catch (ReentrantTickException $exception) {
            self::assertStringContainsString('tick() was called while another operation is in progress.', $exception->getMessage());
        }

        self::assertFalse($context->has('dirty'));
        self::assertSame(0, $machine->getTickCount());
        self::assertSame(1, $machine->tick(new TestInput('retry'))->tickNumber);
    }

    public function testReentrantTickFromMiddlewareIsRejected(): void
    {
        $machine = new StateMachine(new ArrayContext());
        $middleware = new CallbackMiddleware(static function (CycleRequest $request, callable $next) use ($machine): TickResult {
            $machine->tick(new TestInput('nested'));

            return $next($request);
        });

        $machine->registerMiddleware($middleware);
        $machine->registerState(new RecordingState('a'));
        $machine->start('a');

        $this->expectException(ReentrantTickException::class);

        $machine->tick(new TestInput());
    }

    public function testListenersRunAfterCommitAndMayStartNewOperations(): void
    {
        $context = new ArrayContext();
        $machine = new StateMachine($context);
        $order = [];

        $machine->registerStates(
            new RecordingState('a', static fn(): CycleResponse => CycleResponse::fromEvent(new TestEvent('done'))),
            new RecordingState('b'),
            new RecordingState('c', null, static fn(): CycleResponse => CycleResponse::fromEvent(new TestEvent('entered-c'))),
        );
        $machine->subscribe(TestEvent::class, static function (TestEvent $event) use (&$order, $machine): void {
            $order[] = 'event:' . $event->label . '@' . ($machine->getCurrentStateId() ?? 'none');

            if ($event->label === 'done') {
                $machine->transitionTo('c');
            }
        });
        $machine->subscribe(TickCompleted::class, static function () use (&$order, $machine): void {
            $order[] = 'completed@' . ($machine->getCurrentStateId() ?? 'none');
        });
        $machine->start('a');

        $result = $machine->tick(new TestInput());

        self::assertSame('a', $result->toStateId, 'the tick result reflects the tick, not the listener side effect');
        self::assertSame('c', $machine->getCurrentStateId());
        self::assertSame(['event:done@a', 'event:entered-c@c', 'completed@c'], $order);
    }

    public function testTransitionToOutsideTickDispatchesImmediatelyAndReturnsMessages(): void
    {
        $machine = new StateMachine(new ArrayContext());
        $log = new MessageLog();
        $a = new RecordingState('a');
        $b = new RecordingState('b', null, static fn(): CycleResponse => CycleResponse::fromEvent(new TestEvent('hello-b')));

        $machine->registerStates($a, $b);
        $machine->subscribe(EventInterface::class, $log);
        $machine->start('a');
        $log->entries = [];

        $messages = $machine->transitionTo('b');

        self::assertSame(['transition:a->b', 'activated:b<-a', 'event:hello-b'], $log->entries);
        self::assertSame(['transition:a->b', 'activated:b<-a', 'event:hello-b'], array_map([MessageLog::class, 'describe'], $messages));
        self::assertSame(1, $a->leaveCount);
        self::assertSame('b', $machine->getCurrentStateId());
        self::assertSame(0, $machine->getTickCount());
    }

    public function testTransitionToFromMiddlewareIsPartOfTheTick(): void
    {
        $machine = new StateMachine(new ArrayContext());
        $log = new MessageLog();
        $middleware = new CallbackMiddleware(static function (CycleRequest $request, callable $next) use ($machine): TickResult {
            $machine->transitionTo('b');

            return $next($request);
        });

        $machine->registerMiddleware($middleware);
        $machine->registerStates(
            new RecordingState('a', static fn(): CycleResponse => CycleResponse::fromEvent(new TestEvent('processed-by-a'))),
            new RecordingState('b'),
        );
        $machine->subscribe(EventInterface::class, $log);
        $machine->start('a');
        $log->entries = [];

        $result = $machine->tick(new TestInput());

        self::assertSame(['transition:a->b', 'activated:b<-a', 'event:processed-by-a', 'completed:1'], $log->entries);
        self::assertSame('a', $result->fromStateId);
        self::assertSame('b', $result->toStateId);
    }

    public function testUnexpectedInputFromStateRollsBack(): void
    {
        $machine = new StateMachine(new ArrayContext());
        $machine->registerState(new RecordingState('a', static function (CycleRequest $request): CycleResponse {
            if ($request->getInput() instanceof OtherInput) {
                throw new RuntimeException('wrong input');
            }

            return CycleResponse::none();
        }));
        $machine->start('a');

        $this->expectException(RuntimeException::class);

        $machine->tick(new OtherInput());
    }

    // -- snapshots ------------------------------------------------------------------------------

    public function testSnapshotCapturesEverything(): void
    {
        $clock = FrozenClock::at('2026-09-10T12:00:00+00:00');
        $context = new ArrayContext(['status' => 'ready']);
        $machine = new StateMachine($context, clock: $clock);
        $serializable = new SerializableRecordingState('s');
        $serializable->state = ['counter' => 42];

        $machine->registerStates($serializable, new RecordingState('plain'));
        $machine->start('s');
        $machine->tick(new TestInput());

        $snapshot = $machine->snapshot();

        self::assertSame([
            'schemaVersion' => StateSnapshot::SCHEMA_VERSION,
            'createdAt' => '2026-09-10T12:00:00+00:00',
            'tickCount' => 1,
            'currentStateId' => 's',
            'contextState' => ['status' => 'ready'],
            'stateData' => ['s' => ['counter' => 42]],
        ], $snapshot->toArray());
    }

    public function testRestoreRoundTripIsSilentAndResumesTickCount(): void
    {
        $context = new ArrayContext(['status' => 'ready']);
        $machine = new StateMachine($context);
        $original = new SerializableRecordingState('s');
        $original->state = ['counter' => 42];
        $machine->registerState($original);
        $machine->start('s');
        $machine->tick(new TestInput());
        $snapshot = $machine->snapshot();

        $restoredContext = new ArrayContext();
        $restored = new StateMachine($restoredContext);
        $log = new MessageLog();
        $copy = new SerializableRecordingState('s');
        $restored->registerState($copy);
        $restored->subscribe(EventInterface::class, $log);

        $restored->restore($snapshot);

        self::assertTrue($restored->isStarted());
        self::assertSame('s', $restored->getCurrentStateId());
        self::assertSame(1, $restored->getTickCount());
        self::assertSame(['counter' => 42], $copy->restoredState);
        self::assertSame('ready', $restoredContext->getString('status'));
        self::assertSame(0, $copy->enterCount);
        self::assertSame([], $log->entries);

        self::assertSame(2, $restored->tick(new TestInput())->tickNumber);
    }

    public function testRestoreCallsOnResumeForResumableStates(): void
    {
        $context = new ArrayContext();
        $machine = new StateMachine($context);
        $state = new ResumableRecordingState('r');
        $machine->registerState($state);

        $machine->restore(StateSnapshot::create([], 'r', [], 3, new DateTimeImmutable()));

        self::assertSame(1, $state->resumeCount);
        self::assertSame(0, $state->enterCount);
        self::assertTrue($context->getBool('resumed'));
    }

    public function testRestoreWithNullStateLeavesMachineUnstarted(): void
    {
        $machine = new StateMachine(new ArrayContext());
        $machine->registerState(new RecordingState('a'));

        $machine->restore(StateSnapshot::create(['k' => 'v'], null, [], 0, new DateTimeImmutable()));

        self::assertFalse($machine->isStarted());
        self::assertSame('v', $machine->getContext()->getString('k'));

        $machine->start('a');

        self::assertSame('a', $machine->getCurrentStateId());
    }

    public function testRestoreIntoStartedMachineThrows(): void
    {
        $machine = new StateMachine(new ArrayContext());
        $machine->registerState(new RecordingState('a'));
        $machine->start('a');

        $this->expectException(IllegalStateException::class);
        $this->expectExceptionMessage('Cannot restore a snapshot into a state machine that is already started in state "a".');

        $machine->restore(StateSnapshot::create([], 'a', [], 0, new DateTimeImmutable()));
    }

    /**
     * @return iterable<string, array{string|null, array<string, array<string, mixed>>, string}>
     */
    public static function unrestorableSnapshots(): iterable
    {
        yield 'unknown current state' => ['missing', [], 'Invalid snapshot: current state "missing" is not registered.'];
        yield 'unknown state data' => ['plain', ['missing' => []], 'Invalid snapshot: state "missing" is not registered.'];
        yield 'non-serializable state data' => ['plain', ['plain' => ['x' => 1]], 'Invalid snapshot: state "plain" does not implement SerializableStateInterface.'];
    }

    /**
     * @param array<string, array<string, mixed>> $stateData
     *
     * @dataProvider unrestorableSnapshots
     */
    public function testRestoreValidatesAgainstRegisteredStates(?string $currentStateId, array $stateData, string $message): void
    {
        $machine = new StateMachine(new ArrayContext());
        $machine->registerState(new RecordingState('plain'));

        $this->expectException(SnapshotHydrationException::class);
        $this->expectExceptionMessage($message);

        $machine->restore(StateSnapshot::create([], $currentStateId, $stateData, 0, new DateTimeImmutable()));
    }

    public function testInterfaceSubscriptionsObserveAllEvents(): void
    {
        $machine = new StateMachine(new ArrayContext());
        $log = new MessageLog();
        $machine->registerStates(
            new RecordingState('a', static fn(): CycleResponse => CycleResponse::transitionTo('b')->withEvent(new TestEvent('e'))),
            new RecordingState('b'),
        );
        $machine->subscribe(EventInterface::class, $log);
        $machine->start('a');
        $machine->tick(new TestInput());

        self::assertSame([
            'activated:a<-none',
            'transition:a->b',
            'activated:b<-a',
            'event:e',
            'completed:1',
        ], $log->entries);
    }
}
