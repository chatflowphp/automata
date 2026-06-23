# Orchestrator

`Automata\Core\Orchestrator` coordinates the lifecycle and execution flow of a single-active finite-state machine.

## Public API

| Method | Purpose |
| --- | --- |
| `registerAutomaton()` | Register an automaton by id |
| `registerMiddleware()` | Add middleware around each `tick()` |
| `subscribe()` | Register a message listener |
| `activate()` | Set the current active automaton and call `onEnter()` |
| `tick()` | Run one processing cycle on the active automaton |
| `snapshot()` | Export context state, active automaton id, and serializable automaton state |
| `activateFromSnapshot()` | Restore state and resume from a snapshot |
| `getActiveAutomatonId()` | Inspect the currently active automaton id |
| `hasActiveAutomaton()` | Check whether any automaton is active |
| `hasAutomaton()` | Check whether a specific automaton id is registered |

## Registering Automata

Register each automaton before activation:

```php
$orchestrator->registerAutomaton(new IdleState());
$orchestrator->registerAutomaton(new ActiveState());
```

Automaton ids come from `AutomatonInterface::getId()`.

## Activation

Use `activate()` to set the initial active automaton:

```php
$orchestrator->activate(IdleState::ID);
```

Activation behavior:

- resolves the target automaton
- sets it as active
- calls `onEnter()`
- emits `AutomatonActivatedEvent`

If the automaton id is not registered, `activate()` throws `AutomatonNotFoundException`.

## Tick Lifecycle

`tick()` runs one cycle against the current active automaton:

```php
$orchestrator->tick($input);
```

The exact order is:

1. Ensure an active automaton exists
2. Build `CycleRequest`
3. Run middleware in registration order
4. Call `process()` on the active automaton
5. Dispatch commands from the returned `CycleResponse`
6. Dispatch events from the returned `CycleResponse`
7. Emit `CycleCompletedEvent` last

If no active automaton exists, `tick()` throws `AutomatonNotFoundException`.

## Middleware Order

Register middleware in the same order you want it to wrap the cycle:

```php
$orchestrator->registerMiddleware($middlewareOne);
$orchestrator->registerMiddleware($middlewareTwo);
```

Execution order:

- `middlewareOne` before
- `middlewareTwo` before
- active automaton `process()`
- `middlewareTwo` after
- `middlewareOne` after

## Transitions

Any command that implements `TransitionCommandInterface` triggers a transition.

Transition behavior:

- if the next automaton id is different, call `onLeave()` on the current automaton
- set the next automaton as active
- call `onEnter()` on the next automaton
- emit `TransitionAppliedEvent`
- emit `AutomatonActivatedEvent`
- then dispatch the transition command itself on the message bus

Self-transition is a no-op for lifecycle hooks and lifecycle events.

## Message Subscriptions

Subscribe to commands or events by message name:

```php
$orchestrator->subscribe(WorkflowAdvancedEvent::NAME, $handler);
```

The default `MessageBus` dispatches synchronously.

## Introspection

Use the helpers when you need runtime state:

```php
$orchestrator->getActiveAutomatonId();
$orchestrator->hasActiveAutomaton();
$orchestrator->hasAutomaton('workflow.active');
```

## Snapshot Entry Points

Use:

```php
$snapshot = $orchestrator->snapshot();
$orchestrator->activateFromSnapshot($snapshot);
```

For full snapshot semantics, read [Snapshots](snapshots.md).
