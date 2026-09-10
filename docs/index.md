# Automata Docs

`chatflowphp/automata` is a small state machine runtime for flows that advance one input at a time
and must survive between requests: chat dialogs, wizards, approval workflows.

## Build your first flow

1. [Getting Started](getting-started.md), a survey bot built step by step
2. [survey-bot example](../examples/survey-bot/README.md), the same flow as runnable code
3. [Testing](testing.md)

## Understand the runtime

- [State Machine](state-machine.md): lifecycle, tick order, atomicity, re-entrancy
- [States](states.md): `StateInterface`, `AbstractState`, typed input, lifecycle hooks
- [Transitions](transitions.md): transition tables, guards, introspection, diagrams
- [Messaging](messaging.md): commands, events, `CycleResponse`, the message bus
- [Context](context.md): shared state and typed accessors
- [Snapshots](snapshots.md): schema, versioning, migrations, JSON
- [Sessions](sessions.md): stores and the request cycle
- [Middleware](middleware.md): wrapping a tick

## Upgrading

- [Upgrade from 1.x](upgrade-from-1.x.md)

## Public surface

| Concern | Classes |
| --- | --- |
| Runtime | `Automata\Machine\StateMachine`, `TickResult`, `CycleRequest`, `CycleResponse`, `InputInterface` |
| States | `Automata\State\StateInterface`, `AbstractState`, `SerializableStateInterface`, `ResumableStateInterface` |
| Transitions | `Automata\Machine\Transition\TransitionTable`, `TransitionPolicyInterface`, `AllowAllTransitions` |
| Messaging | `Automata\Messaging\CommandInterface`, `EventInterface`, `TransitionCommandInterface`, `TransitionCommand`, `MessageBusInterface`, `MessageBus` |
| Events | `Automata\Events\StateActivated`, `TransitionApplied`, `TickCompleted` |
| Context | `Automata\Context\ContextInterface`, `ArrayContext`, `ContextAccessorsTrait` |
| Snapshots | `Automata\Snapshot\StateSnapshot`, `JsonSnapshotSerializer`, `SnapshotMigrationInterface`, `SnapshotStoreInterface`, `InMemorySnapshotStore`, `Session` |
| Middleware | `Automata\Middleware\TickMiddlewareInterface` |
| Clock | `Automata\Clock\SystemClock`, `FrozenClock` (implement `Psr\Clock\ClockInterface`) |
| Exceptions | `Automata\Exception\*`, all extending `AutomataException` |
