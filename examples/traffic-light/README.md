# Traffic Light Demo

This is the advanced canonical example for `chatflowphp/automata`.

If you are new to the library, start with [docs/getting-started.md](../../docs/getting-started.md) and the [simple-workflow](../simple-workflow/README.md) example first.

## What This Example Covers

`traffic-light` demonstrates the richer runtime model:

- three automata connected by transitions
- middleware around every tick
- domain events observed after transitions
- lifecycle events emitted by the orchestrator
- strict snapshot save, restore, and resumed execution

## Runtime Flow

1. `TrafficLightApplication` creates `TrafficLightContext`, `Orchestrator`, middleware, listeners, and three automata.
2. `Orchestrator::activate()` starts the red-light automaton and calls `onEnter()`.
3. Each `tick()` increments `total_ticks` through middleware and then runs the active automaton.
4. When an automaton decides to switch, it returns a `ChangeColorCommand` plus a `LightColorChangedEvent`.
5. The orchestrator applies the transition first, dispatches lifecycle events, dispatches the command, dispatches the domain event, and emits `CycleCompletedEvent` last.

Because domain events are dispatched after the transition, the `ChangeColorListener` can read the already-updated context and confirm which automaton is active now.

## Components

- `CycleCounterMiddleware` increments `total_ticks`
- `RedLightState`, `GreenLightState`, `YellowLightState` implement the workflow
- `ChangeColorListener` reacts to the domain event after transition completion
- `CycleResponseListener` reacts to `CycleCompletedEvent` for per-tick output
- `run.php` demonstrates snapshot export, restore, and resumed execution

The runtime also emits `TransitionAppliedEvent` and `AutomatonActivatedEvent` when consumers need lifecycle-level observability.

## Snapshot Notes

The example uses `Orchestrator::snapshot()` and `activateFromSnapshot()` directly. For external persistence, use `JsonSnapshotSerializer` to convert the produced `StateSnapshot` to and from JSON.

Deeper reference:

- [docs/orchestrator.md](../../docs/orchestrator.md)
- [docs/snapshots.md](../../docs/snapshots.md)

## Run

```bash
php examples/traffic-light/run.php
```
