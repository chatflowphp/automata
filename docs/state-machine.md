# State Machine

`Automata\Machine\StateMachine` holds exactly one current state and advances it one input at a time.

## Construction

```php
new StateMachine(
    ContextInterface $context,
    ?MessageBusInterface $bus = null,            // default: MessageBus
    ?StateRegistryInterface $states = null,      // default: StateRegistry
    ?TransitionPolicyInterface $transitions = null, // default: AllowAllTransitions
    ?ClockInterface $clock = null,               // default: SystemClock
);
```

## Public API

| Method | Purpose |
| --- | --- |
| `registerState()`, `registerStates()` | Register states by id; duplicates throw `DuplicateStateException` |
| `registerMiddleware()` | Wrap every tick, see [Middleware](middleware.md) |
| `subscribe(class, handler)` | Listen to a message class, see [Messaging](messaging.md) |
| `start(id)` | Make the initial state current and run its `onEnter()`; returns the emitted messages |
| `tick(input)` | Run one cycle; returns `TickResult` |
| `transitionTo(id)` | Explicit transition from outside a tick; returns the emitted messages |
| `snapshot()` | Capture the machine, see [Snapshots](snapshots.md) |
| `restore(snapshot)` | Load a snapshot into a machine that was not started |
| `isStarted()`, `getCurrentStateId()`, `getTickCount()` | Runtime state |
| `hasState()`, `getStateIds()` | Registry introspection |
| `canTransitionTo()`, `getAllowedTransitions()`, `getTransitions()` | Policy introspection |
| `getContext()` | The shared context |

## Lifecycle

```
start(initial) ──► current = initial, onEnter(initial)
tick(input)    ──► middleware ► process() ► commands, transitions, events ► TickCompleted
restore(snap)  ──► current = snap.currentStateId, no hooks except onResume(), no events
```

- `start()` throws `IllegalStateException` when the machine is already started.
- `tick()` and `transitionTo()` throw `IllegalStateException` before `start()` or `restore()`.
- `restore()` throws `IllegalStateException` on a started machine. Build a fresh instance per request.

## Tick order

1. Middleware run in registration order around everything below.
2. `process()` of the current state returns a `CycleResponse`.
3. Commands are applied in order. A `TransitionCommandInterface` first performs the transition:
   policy check, `onLeave()` on the current state, current state changes, `TransitionApplied` and
   `StateActivated` are emitted, `onEnter()` of the new state runs and its response is applied
   the same way (transitions may chain). Then the command itself is emitted.
4. Events are emitted in order.
5. The tick counter increments and the innermost handler returns a `TickResult`.
6. `TickCompleted` is emitted last.
7. Once the outermost middleware returns, every emitted message is dispatched to the bus in order.

A transition to the current state is a no-op: no hooks, no events, policy not consulted.

## Atomicity

Every operation (`start`, `tick`, `transitionTo`) records a checkpoint of the context, the current
state id, the tick counter, and the data of every `SerializableStateInterface` state. If anything
throws, the checkpoint is restored and the exception propagates. Nothing reaches the bus.

Hooks that already ran are not undone. If `onEnter()` of a target state throws, `onLeave()` of the
source state has already executed even though the machine reports the source state as current.
Keep hooks free of external side effects, or make those side effects idempotent.

## Listeners run after commit

Messages are queued during the operation and dispatched after it succeeds. A listener therefore
observes the final context and current state of the tick. A listener may start a new operation,
for example `transitionTo()`, and that operation runs after the current one has fully committed.

A listener that throws stops dispatch of the remaining messages of that operation. The machine
state stays committed.

## Re-entrancy

`tick()` inside a running operation, from a state, a middleware, or a guard, throws
`ReentrantTickException` and the outer operation rolls back. `transitionTo()` from a middleware is
allowed and becomes part of the running tick.

## Transition chains

`onEnter()` may return a transition, so one tick can pass through several states. More than
`StateMachine::MAX_TRANSITIONS_PER_OPERATION` (32) transitions in one operation throws
`InvalidTransitionException`, which usually means two states bounce between each other.

## TickResult

```php
$result = $machine->tick($input);

$result->tickNumber;          // 1-based counter, persisted in snapshots
$result->fromStateId;         // state that handled the input
$result->toStateId;           // state after all transitions
$result->transitioned();      // fromStateId !== toStateId
$result->response;            // the CycleResponse returned by process()
$result->messages;            // every command and event emitted, in order
$result->messagesOf(BotReply::class);
```

Use the result in request handlers instead of subscribing when you only need this tick's output.
