<?php

declare(strict_types=1);

namespace Automata\Tests\Core;

use Automata\Contracts\AutomatonInterface;
use Automata\Contracts\CommandInterface;
use Automata\Contracts\ContextInterface;
use Automata\Contracts\CycleMiddlewareInterface;
use Automata\Contracts\EventInterface;
use Automata\Contracts\InputInterface;
use Automata\Contracts\SerializableAutomatonInterface;
use Automata\Contracts\TransitionCommandInterface;
use Automata\Core\Context\ArrayContext;
use Automata\Core\CycleRequest;
use Automata\Core\CycleResponse;
use Automata\Core\MessageBus;
use Automata\Core\Orchestrator;
use Automata\DTO\StateSnapshot;
use Automata\Events\AutomatonActivatedEvent;
use Automata\Events\CycleCompletedEvent;
use Automata\Events\TransitionAppliedEvent;
use Automata\Exceptions\AutomataException;
use Automata\Exceptions\AutomatonNotFoundException;
use Automata\Exceptions\SnapshotHydrationException;
use Automata\Messages\NoopCommand;
use PHPUnit\Framework\TestCase;

final class OrchestratorTest extends TestCase
{
    public function testRegisterAutomatonAndIntrospectionApiExposePublicState(): void
    {
        $context = new ArrayContext();
        $orchestrator = new Orchestrator($context);
        $automaton = new RecordingAutomaton('demo');

        self::assertFalse($orchestrator->hasActiveAutomaton());
        self::assertNull($orchestrator->getActiveAutomatonId());
        self::assertFalse($orchestrator->hasAutomaton('demo'));

        $orchestrator->registerAutomaton($automaton);

        self::assertTrue($orchestrator->hasAutomaton('demo'));

        $orchestrator->activate('demo');

        self::assertTrue($orchestrator->hasActiveAutomaton());
        self::assertSame('demo', $orchestrator->getActiveAutomatonId());
        self::assertSame(1, $automaton->enterCount);
    }

    public function testTickWithoutActiveAutomatonThrows(): void
    {
        $orchestrator = new Orchestrator(new ArrayContext());

        $this->expectException(AutomatonNotFoundException::class);
        $this->expectException(AutomataException::class);
        $this->expectExceptionMessage('No active automaton is configured.');

        $orchestrator->tick(new TestInput());
    }

    public function testActivateWithUnknownAutomatonThrows(): void
    {
        $orchestrator = new Orchestrator(new ArrayContext());

        $this->expectException(AutomatonNotFoundException::class);
        $this->expectExceptionMessage('Automaton "missing" is not registered.');

        $orchestrator->activate('missing');
    }

    public function testTransitionToUnknownAutomatonThrows(): void
    {
        $context = new ArrayContext();
        $orchestrator = new Orchestrator($context);
        $orchestrator->registerAutomaton(new RecordingAutomaton(
            'source',
            static fn (): CycleResponse => CycleResponse::fromCommand(new CustomTransitionCommand('missing'))
        ));
        $orchestrator->activate('source');

        $this->expectException(AutomatonNotFoundException::class);
        $this->expectExceptionMessage('Automaton "missing" is not registered.');

        $orchestrator->tick(new TestInput());
    }

    public function testTickRunsMiddlewaresInRegistrationOrderAndAllowsResponseAugmentation(): void
    {
        $context = new ArrayContext(['trace' => []]);
        $orchestrator = new Orchestrator($context);
        $events = [];

        $middlewareOne = new class implements CycleMiddlewareInterface {
            public function handle(CycleRequest $request, callable $next): CycleResponse
            {
                $trace = traceFromContext($request->getContext());
                $trace[] = 'middleware.one.before';
                $request->getContext()->set('trace', $trace);

                $response = $next($request);

                $trace = traceFromContext($request->getContext());
                $trace[] = 'middleware.one.after';
                $request->getContext()->set('trace', $trace);

                return $response;
            }
        };

        $middlewareTwo = new class implements CycleMiddlewareInterface {
            public function handle(CycleRequest $request, callable $next): CycleResponse
            {
                $trace = traceFromContext($request->getContext());
                $trace[] = 'middleware.two.before';
                $request->getContext()->set('trace', $trace);

                return $next($request)->withEvent(new TestEvent('middleware.event'));
            }
        };

        $automaton = new RecordingAutomaton('fsm', static function (CycleRequest $request): CycleResponse {
            $trace = traceFromContext($request->getContext());
            $trace[] = 'automaton.process';
            $request->getContext()->set('trace', $trace);

            return CycleResponse::fromCommand(new NoopCommand());
        });

        $orchestrator->registerMiddleware($middlewareOne);
        $orchestrator->registerMiddleware($middlewareTwo);
        $orchestrator->registerAutomaton($automaton);
        $orchestrator->subscribe(TestEvent::NAME, static function (TestEvent $event) use (&$events): void {
            $events[] = $event->label;
        });
        $orchestrator->activate('fsm');
        $orchestrator->tick(new TestInput());

        self::assertSame(
            ['middleware.one.before', 'middleware.two.before', 'automaton.process', 'middleware.one.after'],
            $context->get('trace')
        );
        self::assertSame(['middleware.event'], $events);
    }

    public function testTransitionDispatchesCommandBeforeEventsButAfterStateChangeAndCycleCompletedIsLast(): void
    {
        $context = new ArrayContext();
        $orchestrator = new Orchestrator($context, new MessageBus());
        $log = [];

        $source = new RecordingAutomaton('source', static function (): CycleResponse {
            return CycleResponse::fromCommand(new CustomTransitionCommand('target'))
                ->withEvent(new TestEvent('domain'));
        }, static function (ContextInterface $context): void {
            $context->set('state_color', 'red');
            $context->set('active_automaton_name', 'source');
        });
        $target = new RecordingAutomaton('target', null, static function (ContextInterface $context): void {
            $context->set('state_color', 'green');
            $context->set('active_automaton_name', 'target');
        });

        $orchestrator->registerAutomaton($source);
        $orchestrator->registerAutomaton($target);
        $orchestrator->subscribe(TransitionAppliedEvent::NAME, static function (TransitionAppliedEvent $event) use (&$log): void {
            $log[] = sprintf(
                'transition:%s:%s',
                $event->getFromAutomatonId(),
                $event->getToAutomatonId()
            );
        });
        $orchestrator->subscribe(AutomatonActivatedEvent::NAME, static function (AutomatonActivatedEvent $event) use (&$log): void {
            $previousId = $event->getPreviousAutomatonId();
            $log[] = sprintf(
                'activated:%s:%s',
                $previousId ?? 'none',
                $event->getAutomatonId()
            );
        });
        $orchestrator->subscribe(CustomTransitionCommand::NAME, static function (CustomTransitionCommand $command) use (&$log, $orchestrator, $context): void {
            $stateColor = $context->get('state_color');
            $log[] = sprintf(
                'command:%s:%s:%s',
                $command->getNextAutomatonId(),
                $orchestrator->getActiveAutomatonId(),
                is_string($stateColor) ? $stateColor : 'unknown'
            );
        });
        $orchestrator->subscribe(TestEvent::NAME, static function (TestEvent $event) use (&$log, $orchestrator, $context): void {
            $stateColor = $context->get('state_color');
            $log[] = sprintf(
                'event:%s:%s:%s',
                $event->label,
                $orchestrator->getActiveAutomatonId(),
                is_string($stateColor) ? $stateColor : 'unknown'
            );
        });
        $orchestrator->subscribe(CycleCompletedEvent::NAME, static function (CycleCompletedEvent $event) use (&$log, $orchestrator, $context): void {
            $stateColor = $context->get('state_color');
            $log[] = sprintf(
                'completed:%s:%s:%d',
                $orchestrator->getActiveAutomatonId(),
                is_string($stateColor) ? $stateColor : 'unknown',
                count($event->getResponse()->getEvents())
            );
        });

        $orchestrator->activate('source');
        $orchestrator->tick(new TestInput());

        self::assertSame(
            [
                'activated:none:source',
                'transition:source:target',
                'activated:source:target',
                'command:target:target:green',
                'event:domain:target:green',
                'completed:target:green:1',
            ],
            $log
        );
    }

    public function testSelfTransitionIsNoOpForLifecycle(): void
    {
        $context = new ArrayContext();
        $orchestrator = new Orchestrator($context);
        $automaton = new RecordingAutomaton(
            'self',
            static fn (): CycleResponse => CycleResponse::fromCommand(new CustomTransitionCommand('self')),
            static function (ContextInterface $context): void {
                $context->set('active_automaton_name', 'self');
            }
        );

        $orchestrator->registerAutomaton($automaton);
        $orchestrator->activate('self');
        $orchestrator->tick(new TestInput());

        self::assertSame('self', $orchestrator->getActiveAutomatonId());
        self::assertSame(1, $automaton->enterCount);
        self::assertSame(0, $automaton->leaveCount);
    }

    public function testSelfTransitionDoesNotEmitLifecycleEvents(): void
    {
        $context = new ArrayContext();
        $orchestrator = new Orchestrator($context);
        $log = [];
        $automaton = new RecordingAutomaton(
            'self',
            static fn (): CycleResponse => CycleResponse::fromCommand(new CustomTransitionCommand('self'))
        );

        $orchestrator->registerAutomaton($automaton);
        $orchestrator->subscribe(TransitionAppliedEvent::NAME, static function () use (&$log): void {
            $log[] = 'transition';
        });
        $orchestrator->subscribe(AutomatonActivatedEvent::NAME, static function (AutomatonActivatedEvent $event) use (&$log): void {
            $log[] = sprintf('activated:%s', $event->getAutomatonId());
        });

        $orchestrator->activate('self');
        $orchestrator->tick(new TestInput());

        self::assertSame(['activated:self'], $log);
    }

    public function testSnapshotRoundTripRestoresSerializableState(): void
    {
        $context = new ArrayContext(['status' => TestBackedEnum::READY]);
        $orchestrator = new Orchestrator($context);
        $automaton = new SerializableRecordingAutomaton('snapshot');
        $automaton->setState(['counter' => 42, 'flag' => TestBackedEnum::READY]);

        $orchestrator->registerAutomaton($automaton);
        $orchestrator->activate('snapshot');

        $snapshot = $orchestrator->snapshot();

        self::assertSame(
            [
                'contextState' => ['status' => 'ready'],
                'fsmAutomatonId' => 'snapshot',
                'automataStates' => ['snapshot' => ['counter' => 42, 'flag' => 'ready']],
            ],
            $snapshot->toArray()
        );

        $restoredContext = new ArrayContext();
        $restoredOrchestrator = new Orchestrator($restoredContext);
        $restoredAutomaton = new SerializableRecordingAutomaton('snapshot');
        $restoredOrchestrator->registerAutomaton($restoredAutomaton);
        $restoredOrchestrator->activateFromSnapshot($snapshot);

        self::assertSame('snapshot', $restoredOrchestrator->getActiveAutomatonId());
        self::assertSame(['counter' => 42, 'flag' => 'ready'], $restoredAutomaton->restoredState);
        self::assertSame('ready', $restoredContext->get('status'));
    }

    public function testRestoreSnapshotFailsWhenActiveAutomatonIsMissing(): void
    {
        $orchestrator = new Orchestrator(new ArrayContext());
        $snapshot = StateSnapshot::fromArray([
            'contextState' => [],
            'fsmAutomatonId' => 'missing',
            'automataStates' => [],
        ]);

        $this->expectException(SnapshotHydrationException::class);
        $this->expectExceptionMessage('Invalid snapshot: active automaton "missing" is not registered.');

        $orchestrator->activateFromSnapshot($snapshot);
    }

    public function testRestoreSnapshotFailsWhenAutomatonCannotRestoreState(): void
    {
        $orchestrator = new Orchestrator(new ArrayContext());
        $orchestrator->registerAutomaton(new RecordingAutomaton('plain'));
        $snapshot = StateSnapshot::fromArray([
            'contextState' => [],
            'fsmAutomatonId' => 'plain',
            'automataStates' => ['plain' => ['counter' => 1]],
        ]);

        $this->expectException(SnapshotHydrationException::class);
        $this->expectExceptionMessage('Invalid snapshot: automaton "plain" does not support state restoration.');

        $orchestrator->activateFromSnapshot($snapshot);
    }

    public function testSnapshotFailsWhenAutomatonStateContainsNonBackedEnum(): void
    {
        $orchestrator = new Orchestrator(new ArrayContext());
        $automaton = new SerializableRecordingAutomaton('snapshot');
        $automaton->state = ['enum' => TestPureEnum::ONE];
        $orchestrator->registerAutomaton($automaton);
        $orchestrator->activate('snapshot');

        $this->expectException(SnapshotHydrationException::class);
        $this->expectExceptionMessage('non-backed enums are not supported');

        $orchestrator->snapshot();
    }
}

final class TestInput implements InputInterface
{
}

final class TestEvent implements EventInterface
{
    public const NAME = 'test.event';

    public function __construct(public readonly string $label)
    {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPayload(): string
    {
        return $this->label;
    }
}

final class CustomTransitionCommand implements CommandInterface, TransitionCommandInterface
{
    public const NAME = 'test.transition';

    public function __construct(private readonly string $nextAutomatonId)
    {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPayload(): string
    {
        return $this->nextAutomatonId;
    }

    public function getNextAutomatonId(): string
    {
        return $this->nextAutomatonId;
    }
}

class RecordingAutomaton implements AutomatonInterface
{
    public int $enterCount = 0;
    public int $leaveCount = 0;

    private ?\Closure $process;
    private ?\Closure $onEnter;
    private ?\Closure $onLeave;

    /**
     * @param null|callable(CycleRequest):CycleResponse $process
     * @param null|callable(ContextInterface):void $onEnter
     * @param null|callable(ContextInterface):void $onLeave
     */
    public function __construct(
        private readonly string $id,
        ?callable $process = null,
        ?callable $onEnter = null,
        ?callable $onLeave = null
    ) {
        $this->process = $process !== null ? \Closure::fromCallable($process) : null;
        $this->onEnter = $onEnter !== null ? \Closure::fromCallable($onEnter) : null;
        $this->onLeave = $onLeave !== null ? \Closure::fromCallable($onLeave) : null;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function onEnter(ContextInterface $context): void
    {
        $this->enterCount++;

        if ($this->onEnter !== null) {
            ($this->onEnter)($context);
        }
    }

    public function process(CycleRequest $request): CycleResponse
    {
        if ($this->process !== null) {
            /** @var CycleResponse $response */
            $response = ($this->process)($request);

            return $response;
        }

        return CycleResponse::fromCommand(new NoopCommand());
    }

    public function onLeave(ContextInterface $context): void
    {
        $this->leaveCount++;

        if ($this->onLeave !== null) {
            ($this->onLeave)($context);
        }
    }
}

/**
 * @return list<string>
 */
function traceFromContext(ContextInterface $context): array
{
    $trace = $context->get('trace', []);

    if (!is_array($trace)) {
        return [];
    }

    return array_values(array_map(
        static fn (mixed $item): string => is_string($item) ? $item : 'invalid',
        $trace
    ));
}

final class SerializableRecordingAutomaton extends RecordingAutomaton implements SerializableAutomatonInterface
{
    /**
     * @var array<string, mixed>
     */
    public array $state = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $restoredState = null;

    /**
     * @return array<string, mixed>
     */
    public function getState(): array
    {
        return $this->state;
    }

    /**
     * @param array<string, mixed> $state
     */
    public function setState(array $state): void
    {
        $this->state = $state;
        $this->restoredState = $state;
    }
}

enum TestBackedEnum: string
{
    case READY = 'ready';
}

enum TestPureEnum
{
    case ONE;
}
