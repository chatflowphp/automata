<?php

declare(strict_types=1);

namespace Automata\Machine;

use Automata\Clock\SystemClock;
use Automata\Context\ContextInterface;
use Automata\Events\StateActivated;
use Automata\Events\TickCompleted;
use Automata\Events\TransitionApplied;
use Automata\Exception\IllegalStateException;
use Automata\Exception\InvalidTransitionException;
use Automata\Exception\ReentrantTickException;
use Automata\Exception\SnapshotHydrationException;
use Automata\Machine\Transition\AllowAllTransitions;
use Automata\Machine\Transition\TransitionPolicyInterface;
use Automata\Messaging\MessageBus;
use Automata\Messaging\MessageBusInterface;
use Automata\Messaging\TransitionCommandInterface;
use Automata\Middleware\TickMiddlewareInterface;
use Automata\Snapshot\StateSnapshot;
use Automata\State\ResumableStateInterface;
use Automata\State\SerializableStateInterface;
use Automata\State\StateInterface;
use Automata\State\StateRegistry;
use Automata\State\StateRegistryInterface;
use Psr\Clock\ClockInterface;
use Throwable;

/**
 * State machine with exactly one current state, driven by discrete ticks.
 *
 * Every mutating operation (start, tick, transitionTo) is atomic: the machine records a checkpoint,
 * runs the operation, and on any exception restores context, current state, serializable state data,
 * and the tick counter. Messages emitted during the operation are handed to the bus only after it
 * commits, so listeners always observe a consistent machine.
 */
final class StateMachine
{
    /**
     * Upper bound for transitions chained through onEnter() inside one operation.
     */
    public const MAX_TRANSITIONS_PER_OPERATION = 32;

    private readonly MessageBusInterface $bus;

    private readonly StateRegistryInterface $states;

    private readonly TransitionPolicyInterface $transitions;

    private readonly ClockInterface $clock;

    /**
     * @var list<TickMiddlewareInterface>
     */
    private array $middlewares = [];

    private ?string $currentStateId = null;

    private int $tickCount = 0;

    private bool $operationInProgress = false;

    /**
     * @var list<object>
     */
    private array $outbox = [];

    private int $transitionsInOperation = 0;

    public function __construct(
        private readonly ContextInterface $context,
        ?MessageBusInterface $bus = null,
        ?StateRegistryInterface $states = null,
        ?TransitionPolicyInterface $transitions = null,
        ?ClockInterface $clock = null,
    ) {
        $this->bus = $bus ?? new MessageBus();
        $this->states = $states ?? new StateRegistry();
        $this->transitions = $transitions ?? new AllowAllTransitions();
        $this->clock = $clock ?? new SystemClock();
    }

    // -- configuration -------------------------------------------------------------------------

    public function registerState(StateInterface $state): void
    {
        $this->states->register($state);
    }

    public function registerStates(StateInterface ...$states): void
    {
        foreach ($states as $state) {
            $this->states->register($state);
        }
    }

    public function registerMiddleware(TickMiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $messageClass
     * @param callable(T): void $handler
     */
    public function subscribe(string $messageClass, callable $handler): void
    {
        $this->bus->subscribe($messageClass, $handler);
    }

    // -- introspection -------------------------------------------------------------------------

    public function getContext(): ContextInterface
    {
        return $this->context;
    }

    public function getTransitions(): TransitionPolicyInterface
    {
        return $this->transitions;
    }

    public function isStarted(): bool
    {
        return $this->currentStateId !== null;
    }

    public function getCurrentStateId(): ?string
    {
        return $this->currentStateId;
    }

    public function hasState(string $stateId): bool
    {
        return $this->states->has($stateId);
    }

    /**
     * @return list<string>
     */
    public function getStateIds(): array
    {
        return array_keys($this->states->all());
    }

    public function getTickCount(): int
    {
        return $this->tickCount;
    }

    /**
     * Whether a transition to the state would be accepted right now. A self-transition is always
     * accepted because it is a no-op.
     */
    public function canTransitionTo(string $stateId): bool
    {
        if ($this->currentStateId === null || !$this->states->has($stateId)) {
            return false;
        }

        if ($this->currentStateId === $stateId) {
            return true;
        }

        return $this->transitions->isAllowed($this->currentStateId, $stateId, $this->context);
    }

    /**
     * Registered states, other than the current one, that the policy allows right now.
     *
     * @return list<string>
     */
    public function getAllowedTransitions(): array
    {
        $current = $this->currentStateId;

        if ($current === null) {
            return [];
        }

        $allowed = [];

        foreach (array_keys($this->states->all()) as $stateId) {
            if ($stateId !== $current && $this->transitions->isAllowed($current, $stateId, $this->context)) {
                $allowed[] = $stateId;
            }
        }

        return $allowed;
    }

    // -- lifecycle -----------------------------------------------------------------------------

    /**
     * Makes the initial state current and runs its onEnter().
     *
     * @return list<object> Messages emitted while starting, already dispatched to the bus.
     *
     * @throws IllegalStateException When the machine is already started.
     */
    public function start(string $initialStateId): array
    {
        if ($this->currentStateId !== null) {
            throw new IllegalStateException(\sprintf(
                'State machine is already started in state "%s".',
                $this->currentStateId,
            ));
        }

        $state = $this->states->get($initialStateId);

        return $this->runOperation(function () use ($state, $initialStateId): array {
            $this->currentStateId = $initialStateId;
            $this->emit(new StateActivated($initialStateId, null));
            $this->applyResponse($state->onEnter($this->context));

            return $this->outbox;
        });
    }

    /**
     * Explicit transition from outside a tick, for example an operator resetting a flow.
     * Inside a tick, prefer returning a transition command from the state.
     *
     * @return list<object> Messages emitted by the transition, already dispatched unless nested in a tick.
     */
    public function transitionTo(string $targetStateId): array
    {
        $fromStateId = $this->requireCurrentStateId('transition');

        if ($fromStateId === $targetStateId) {
            return [];
        }

        return $this->runOperation(function () use ($fromStateId, $targetStateId): array {
            $offset = \count($this->outbox);
            $this->transition($fromStateId, $targetStateId);

            return \array_slice($this->outbox, $offset);
        });
    }

    /**
     * Runs one cycle: middleware, process() of the current state, commands, transitions, events.
     *
     * @throws ReentrantTickException When called from inside a running operation.
     * @throws IllegalStateException When the machine is not started.
     */
    public function tick(InputInterface $input): TickResult
    {
        if ($this->operationInProgress) {
            throw ReentrantTickException::create();
        }

        $fromStateId = $this->requireCurrentStateId('tick');
        $state = $this->states->get($fromStateId);
        $request = new CycleRequest($input, $this->context);

        return $this->runOperation(function () use ($request, $state, $fromStateId): TickResult {
            $core = function (CycleRequest $cycleRequest) use ($state, $fromStateId): TickResult {
                $response = $state->process($cycleRequest);
                $this->applyResponse($response);
                $this->tickCount++;

                return new TickResult(
                    $this->tickCount,
                    $fromStateId,
                    $this->currentStateId ?? $fromStateId,
                    $response,
                    $this->outbox,
                );
            };

            $pipeline = $core;

            foreach (array_reverse($this->middlewares) as $middleware) {
                $next = $pipeline;
                $pipeline = static fn(CycleRequest $cycleRequest): TickResult => $middleware->handle($cycleRequest, $next);
            }

            $result = $pipeline($request);
            $this->emit(new TickCompleted($request, $result));

            return $result;
        });
    }

    // -- snapshots -----------------------------------------------------------------------------

    /**
     * Captures context, current state, serializable state data, and the tick counter.
     * Safe to call from middleware after $next() to persist the committed result.
     */
    public function snapshot(): StateSnapshot
    {
        return StateSnapshot::create(
            $this->context->getState(),
            $this->currentStateId,
            $this->collectStateData(),
            $this->tickCount,
            $this->clock->now(),
        );
    }

    /**
     * Loads a snapshot into a machine that has not been started. No lifecycle hooks except
     * ResumableStateInterface::onResume() run, and no events are emitted.
     *
     * @throws IllegalStateException When the machine is already started.
     * @throws SnapshotHydrationException When the snapshot references unknown or non-serializable states.
     */
    public function restore(StateSnapshot $snapshot): void
    {
        if ($this->currentStateId !== null) {
            throw new IllegalStateException(\sprintf(
                'Cannot restore a snapshot into a state machine that is already started in state "%s". Use a fresh instance.',
                $this->currentStateId,
            ));
        }

        $this->assertRestorable($snapshot);

        $this->context->setState($snapshot->contextState);

        foreach ($snapshot->stateData as $stateId => $data) {
            $state = $this->states->get($stateId);

            if ($state instanceof SerializableStateInterface) {
                $state->setState($data);
            }
        }

        $this->tickCount = $snapshot->tickCount;
        $this->currentStateId = $snapshot->currentStateId;

        if ($snapshot->currentStateId === null) {
            return;
        }

        $current = $this->states->get($snapshot->currentStateId);

        if ($current instanceof ResumableStateInterface) {
            $current->onResume($this->context);
        }
    }

    // -- internals -----------------------------------------------------------------------------

    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    private function runOperation(callable $operation): mixed
    {
        if ($this->operationInProgress) {
            return $operation();
        }

        $this->operationInProgress = true;
        $this->outbox = [];
        $this->transitionsInOperation = 0;
        $checkpoint = $this->checkpoint();

        try {
            $result = $operation();
        } catch (Throwable $exception) {
            $this->rollback($checkpoint);
            $this->outbox = [];
            $this->operationInProgress = false;

            throw $exception;
        }

        $this->operationInProgress = false;

        foreach ($this->drainOutbox() as $message) {
            $this->bus->dispatch($message);
        }

        return $result;
    }

    /**
     * @return list<object>
     */
    private function drainOutbox(): array
    {
        $messages = $this->outbox;
        $this->outbox = [];

        return $messages;
    }

    private function transition(string $fromStateId, string $targetStateId): void
    {
        $target = $this->states->get($targetStateId);

        if (!$this->transitions->isAllowed($fromStateId, $targetStateId, $this->context)) {
            throw InvalidTransitionException::notAllowed($fromStateId, $targetStateId);
        }

        if (++$this->transitionsInOperation > self::MAX_TRANSITIONS_PER_OPERATION) {
            throw InvalidTransitionException::chainTooLong(self::MAX_TRANSITIONS_PER_OPERATION);
        }

        $this->states->get($fromStateId)->onLeave($this->context);
        $this->currentStateId = $targetStateId;
        $this->emit(new TransitionApplied($fromStateId, $targetStateId));
        $this->emit(new StateActivated($targetStateId, $fromStateId));
        $this->applyResponse($target->onEnter($this->context));
    }

    private function applyResponse(CycleResponse $response): void
    {
        foreach ($response->getCommands() as $command) {
            if ($command instanceof TransitionCommandInterface) {
                $fromStateId = $this->requireCurrentStateId('transition');
                $targetStateId = $command->getTargetStateId();

                if ($fromStateId !== $targetStateId) {
                    $this->transition($fromStateId, $targetStateId);
                }
            }

            $this->emit($command);
        }

        foreach ($response->getEvents() as $event) {
            $this->emit($event);
        }
    }

    private function emit(object $message): void
    {
        $this->outbox[] = $message;
    }

    private function requireCurrentStateId(string $operation): string
    {
        return $this->currentStateId ?? throw new IllegalStateException(\sprintf(
            'Cannot %s: state machine is not started. Call start() or restore() first.',
            $operation,
        ));
    }

    /**
     * @return array{
     *     context: array<string, mixed>,
     *     currentStateId: string|null,
     *     tickCount: int,
     *     stateData: array<string, array<string, mixed>>
     * }
     */
    private function checkpoint(): array
    {
        return [
            'context' => $this->context->getState(),
            'currentStateId' => $this->currentStateId,
            'tickCount' => $this->tickCount,
            'stateData' => $this->collectStateData(),
        ];
    }

    /**
     * @param array{
     *     context: array<string, mixed>,
     *     currentStateId: string|null,
     *     tickCount: int,
     *     stateData: array<string, array<string, mixed>>
     * } $checkpoint
     */
    private function rollback(array $checkpoint): void
    {
        $this->context->setState($checkpoint['context']);
        $this->currentStateId = $checkpoint['currentStateId'];
        $this->tickCount = $checkpoint['tickCount'];

        foreach ($checkpoint['stateData'] as $stateId => $data) {
            $state = $this->states->get($stateId);

            if ($state instanceof SerializableStateInterface) {
                $state->setState($data);
            }
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function collectStateData(): array
    {
        $data = [];

        foreach ($this->states->all() as $stateId => $state) {
            if ($state instanceof SerializableStateInterface) {
                $data[$stateId] = $state->getState();
            }
        }

        return $data;
    }

    private function assertRestorable(StateSnapshot $snapshot): void
    {
        if ($snapshot->currentStateId !== null && !$this->states->has($snapshot->currentStateId)) {
            throw new SnapshotHydrationException(\sprintf(
                'Invalid snapshot: current state "%s" is not registered.',
                $snapshot->currentStateId,
            ));
        }

        foreach (array_keys($snapshot->stateData) as $stateId) {
            if (!$this->states->has($stateId)) {
                throw new SnapshotHydrationException(\sprintf(
                    'Invalid snapshot: state "%s" is not registered.',
                    $stateId,
                ));
            }

            if (!$this->states->get($stateId) instanceof SerializableStateInterface) {
                throw new SnapshotHydrationException(\sprintf(
                    'Invalid snapshot: state "%s" does not implement SerializableStateInterface.',
                    $stateId,
                ));
            }
        }
    }
}
