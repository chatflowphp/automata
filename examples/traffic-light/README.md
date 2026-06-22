# Traffic Light Demo

This example demonstrates the library's canonical execution model: one active automaton, middleware around each tick, transition commands, domain events, and strict snapshot restore.

## Runtime Flow

1. `TrafficLightApplication` creates `TrafficLightContext`, `Orchestrator`, middleware, listeners, and three automata.
2. `Orchestrator::activate()` starts the red-light automaton and calls `onEnter()`.
3. Each `tick()` increments `total_ticks` through middleware and then runs the active automaton.
4. When an automaton decides to switch, it returns a `ChangeColorCommand` plus a `LightColorChangedEvent`.
5. The orchestrator applies the transition first, publishes lifecycle events, publishes the command, publishes the domain event, then emits `CycleCompletedEvent`.

Because events are dispatched after the transition, the `ChangeColorListener` can read the already-updated context and confirm which automaton is active now.

## Components

- `CycleCounterMiddleware` increments `total_ticks`
- `RedLightState`, `GreenLightState`, `YellowLightState` implement the FSM
- `ChangeColorListener` reacts to the domain event after transition completion
- `CycleResponseListener` reacts to `CycleCompletedEvent` for per-tick state output
- `run.php` demonstrates snapshot export, restore, and resumed execution
The core also emits `TransitionAppliedEvent` and `AutomatonActivatedEvent` if consumers need lifecycle-level observability.

## Snapshot Notes

The example uses `Orchestrator::snapshot()` and `activateFromSnapshot()` directly. For external persistence, use `JsonSnapshotSerializer` to convert the produced `StateSnapshot` to and from JSON.

## Run

```bash
php examples/traffic-light/run.php
```
