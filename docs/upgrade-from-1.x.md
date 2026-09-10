# Upgrade from 1.x

Version 2.0 is a rewrite. Every 1.x class is renamed or replaced. Snapshots written by 1.x can be
read through `LegacyV1Migration`.

## Class map

| 1.x | 2.x |
| --- | --- |
| `Automata\Core\Orchestrator` | `Automata\Machine\StateMachine` |
| `Automata\Contracts\AutomatonInterface` | `Automata\State\StateInterface` (+ `AbstractState`) |
| `Automata\Contracts\SerializableAutomatonInterface` | `Automata\State\SerializableStateInterface` |
| `Automata\Contracts\AutomatonRegistryInterface` / `Core\AutomatonRegistry` | `Automata\State\StateRegistryInterface` / `StateRegistry` |
| `Automata\Contracts\ContextInterface` / `Core\Context\ArrayContext` | `Automata\Context\ContextInterface` / `ArrayContext` |
| `Automata\Contracts\InputInterface` | `Automata\Machine\InputInterface` |
| `Automata\Core\CycleRequest`, `CycleResponse` | `Automata\Machine\CycleRequest`, `CycleResponse` |
| `Automata\Contracts\CycleMiddlewareInterface` | `Automata\Middleware\TickMiddlewareInterface` |
| `Automata\Contracts\MessageInterface` | removed; messages are plain objects |
| `Automata\Contracts\CommandInterface`, `EventInterface` | `Automata\Messaging\CommandInterface`, `EventInterface` |
| `Automata\Contracts\TransitionCommandInterface` / `Messages\TransitionCommand` | `Automata\Messaging\TransitionCommandInterface` / `TransitionCommand` |
| `Automata\Messages\AbstractCommand`, `NoopCommand` | removed |
| `Automata\Contracts\MessageBusInterface` / `Core\MessageBus` | `Automata\Messaging\MessageBusInterface` / `MessageBus` |
| `Automata\Events\AutomatonActivatedEvent` | `Automata\Events\StateActivated` |
| `Automata\Events\TransitionAppliedEvent` | `Automata\Events\TransitionApplied` |
| `Automata\Events\CycleCompletedEvent` | `Automata\Events\TickCompleted` |
| `Automata\DTO\StateSnapshot` | `Automata\Snapshot\StateSnapshot` |
| `Automata\Core\State\JsonSnapshotSerializer` | `Automata\Snapshot\JsonSnapshotSerializer` |
| `Automata\Core\State\StateNormalizer` | `Automata\Context\StateNormalizer` |
| `Automata\Exceptions\*` | `Automata\Exception\*` |

## Method map

| 1.x | 2.x |
| --- | --- |
| `registerAutomaton()` | `registerState()`, `registerStates()` |
| `activate(id)` | `start(id)` for the first state; `transitionTo(id)` afterwards |
| `activateFromSnapshot()` | `restore()`, only on a fresh machine |
| `tick(): void` | `tick(): TickResult` |
| `getActiveAutomatonId()`, `hasActiveAutomaton()`, `hasAutomaton()` | `getCurrentStateId()`, `isStarted()`, `hasState()` |
| `subscribe(name, handler)` | `subscribe(MessageClass::class, handler)` |
| `onEnter(): void` | `onEnter(): CycleResponse` (return `CycleResponse::none()`) |
| `MessageInterface::getName()`, `getPayload()` | removed; expose typed properties |
| `AutomatonActivatedEvent::getAutomatonId()` | `StateActivated::$stateId` |

## Behaviour changes

- Ticks are atomic and messages are dispatched after commit. Listeners no longer run in the
  middle of a tick and never observe a state that is later rolled back.
- `tick()` from inside a state, middleware, or guard throws `ReentrantTickException`.
- `restore()` emits nothing and calls no `onEnter()`. 1.x emitted activation events on restore.
- `start()` on a started machine throws. 1.x silently re-activated without `onLeave()`.
- Registering two states with the same id throws `DuplicateStateException`. 1.x overwrote.
- Middleware wraps the whole tick, including transitions, and returns `TickResult`.
- Transition policies are new; the default still allows every transition.
- Snapshots carry `schemaVersion`, `tickCount`, and `createdAt`. Floats keep their type in JSON.
- Numeric-string context keys are rejected on write instead of failing at snapshot time.
- The library depends on `psr/clock`.

## Migrating stored snapshots

```php
$serializer = new JsonSnapshotSerializer([new LegacyV1Migration()]);
```
