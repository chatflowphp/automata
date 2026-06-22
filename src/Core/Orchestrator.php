<?php

declare(strict_types=1);

namespace Automata\Core;

use Automata\Contracts\AutomatonInterface;
use Automata\Contracts\AutomatonRegistryInterface;
use Automata\Contracts\CommandInterface;
use Automata\Contracts\ContextInterface;
use Automata\Contracts\CycleMiddlewareInterface;
use Automata\Contracts\InputInterface;
use Automata\Contracts\MessageBusInterface;
use Automata\Contracts\SerializableAutomatonInterface;
use Automata\Contracts\TransitionCommandInterface;
use Automata\DTO\StateSnapshot;
use Automata\Events\AutomatonActivatedEvent;
use Automata\Events\CycleCompletedEvent;
use Automata\Events\TransitionAppliedEvent;
use Automata\Exceptions\AutomatonNotFoundException;
use Automata\Exceptions\InvalidTransitionException;
use Automata\Exceptions\SnapshotHydrationException;
use Automata\Messages\NoopCommand;

/**
 * Coordinates lifecycle and execution flow of a single-active finite-state machine.
 */
class Orchestrator
{
    private AutomatonRegistryInterface $automata;

    /**
     * @var CycleMiddlewareInterface[]
     */
    private array $middlewares = [];

    private ?string $fsmAutomatonId = null;

    private MessageBusInterface $bus;

    public function __construct(
        private readonly ContextInterface $context,
        ?MessageBusInterface $bus = null,
        ?AutomatonRegistryInterface $automata = null
    ) {
        $this->bus = $bus ?? new MessageBus();
        $this->automata = $automata ?? new AutomatonRegistry();
    }

    public function registerAutomaton(AutomatonInterface $automaton): void
    {
        $this->automata->register($automaton);
    }

    public function subscribe(string $messageName, callable $handler): void
    {
        $this->bus->subscribe($messageName, $handler);
    }

    public function registerMiddleware(CycleMiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function getActiveAutomatonId(): ?string
    {
        return $this->fsmAutomatonId;
    }

    public function hasActiveAutomaton(): bool
    {
        return $this->fsmAutomatonId !== null;
    }

    public function hasAutomaton(string $automatonId): bool
    {
        return $this->automata->hasAutomaton($automatonId);
    }

    /**
     * @throws AutomatonNotFoundException
     */
    public function activate(string $automatonId): void
    {
        $this->activateAutomaton($automatonId, callLeave: false, emitLifecycleEvents: true);
    }

    /**
     * @throws SnapshotHydrationException
     * @throws AutomatonNotFoundException
     */
    public function activateFromSnapshot(StateSnapshot $snapshot): void
    {
        $snapshot = StateSnapshot::fromArray($snapshot->toArray());
        $this->assertSnapshotCanBeApplied($snapshot);
        $this->applySnapshot($snapshot);

        if ($snapshot->fsmAutomatonId === null) {
            $this->fsmAutomatonId = null;
            return;
        }

        $this->activateAutomaton(
            $snapshot->fsmAutomatonId,
            callLeave: false,
            callEnter: false,
            emitLifecycleEvents: true
        );
    }

    /**
     * @throws AutomatonNotFoundException
     * @throws InvalidTransitionException
     */
    public function tick(InputInterface $input): void
    {
        if ($this->fsmAutomatonId === null) {
            throw new AutomatonNotFoundException('No active automaton is configured.');
        }

        $currentAutomaton = $this->automata->getAutomaton($this->fsmAutomatonId);
        $request = new CycleRequest($input, $this->context);

        $pipeline = function (CycleRequest $cycleRequest) use ($currentAutomaton): CycleResponse {
            return $currentAutomaton->process($cycleRequest);
        };

        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = $pipeline;
            $pipeline = static function (CycleRequest $cycleRequest) use ($middleware, $next): CycleResponse {
                return $middleware->handle($cycleRequest, $next);
            };
        }

        $response = $pipeline($request);
        $this->dispatchCommands($response);
        $this->dispatchEvents($response);

        $this->bus->dispatch(new CycleCompletedEvent($request, $response));
    }

    /**
     * @throws SnapshotHydrationException
     */
    public function snapshot(): StateSnapshot
    {
        $automataStates = [];

        foreach ($this->automata->all() as $automatonId => $automaton) {
            if (!$automaton instanceof SerializableAutomatonInterface) {
                continue;
            }

            $automataStates[$automatonId] = $automaton->getState();
        }

        return StateSnapshot::fromArray([
            'contextState' => $this->context->getState(),
            'fsmAutomatonId' => $this->fsmAutomatonId,
            'automataStates' => $automataStates,
        ]);
    }

    private function handleTransition(TransitionCommandInterface $command): void
    {
        $this->activateAutomaton(
            $command->getNextAutomatonId(),
            callLeave: true,
            emitLifecycleEvents: true
        );
    }

    private function activateAutomaton(
        string $targetAutomatonId,
        bool $callLeave,
        bool $callEnter = true,
        bool $emitLifecycleEvents = false
    ): void {
        $targetAutomaton = $this->automata->getAutomaton($targetAutomatonId);
        $previousAutomatonId = $this->fsmAutomatonId;

        if ($callLeave) {
            if ($this->fsmAutomatonId === null) {
                throw new InvalidTransitionException('Cannot transition because no active automaton is configured.');
            }

            if ($this->fsmAutomatonId === $targetAutomatonId) {
                return;
            }

            $currentAutomaton = $this->automata->getAutomaton($this->fsmAutomatonId);
            $currentAutomaton->onLeave($this->context);
        }

        $this->fsmAutomatonId = $targetAutomatonId;

        if ($callEnter) {
            $targetAutomaton->onEnter($this->context);
        }

        if (!$emitLifecycleEvents) {
            return;
        }

        if ($previousAutomatonId !== null) {
            $this->bus->dispatch(new TransitionAppliedEvent($previousAutomatonId, $targetAutomatonId));
        }

        $this->bus->dispatch(new AutomatonActivatedEvent($targetAutomatonId, $previousAutomatonId));
    }

    /**
     * @throws SnapshotHydrationException
     */
    private function assertSnapshotCanBeApplied(StateSnapshot $snapshot): void
    {
        $registeredAutomata = $this->automata->all();

        if ($snapshot->fsmAutomatonId !== null && !isset($registeredAutomata[$snapshot->fsmAutomatonId])) {
            throw new SnapshotHydrationException(sprintf(
                'Invalid snapshot: active automaton "%s" is not registered.',
                $snapshot->fsmAutomatonId
            ));
        }

        foreach ($snapshot->automataStates as $automatonId => $state) {
            $automaton = $registeredAutomata[$automatonId] ?? null;

            if ($automaton === null) {
                throw new SnapshotHydrationException(sprintf(
                    'Invalid snapshot: automaton "%s" is not registered.',
                    $automatonId
                ));
            }

            if (!$automaton instanceof SerializableAutomatonInterface) {
                throw new SnapshotHydrationException(sprintf(
                    'Invalid snapshot: automaton "%s" does not support state restoration.',
                    $automatonId
                ));
            }

        }
    }

    private function applySnapshot(StateSnapshot $snapshot): void
    {
        $this->context->setState($snapshot->contextState);

        foreach ($snapshot->automataStates as $automatonId => $state) {
            $automaton = $this->automata->getAutomaton($automatonId);

            if (!$automaton instanceof SerializableAutomatonInterface) {
                throw new SnapshotHydrationException(sprintf(
                    'Invalid snapshot: automaton "%s" does not support state restoration.',
                    $automatonId
                ));
            }

            $automaton->setState($state);
        }
    }

    private function dispatchCommands(CycleResponse $response): void
    {
        foreach ($response->getCommands() as $command) {
            $this->dispatchCommand($command);
        }
    }

    private function dispatchEvents(CycleResponse $response): void
    {
        foreach ($response->getEvents() as $event) {
            $this->bus->dispatch($event);
        }
    }

    private function dispatchCommand(CommandInterface $command): void
    {
        if ($command instanceof NoopCommand) {
            return;
        }

        if ($command instanceof TransitionCommandInterface) {
            $this->handleTransition($command);
        }

        $this->bus->dispatch($command);
    }
}
