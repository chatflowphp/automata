# Simple Workflow Example

The shortest runnable example in the repository: two states, one middleware, one event listener,
one snapshot round trip.

It demonstrates:

- `StateMachine` and `ArrayContext`
- two states built on `AbstractState` with a typed input (`AdvanceInput`)
- `onEnter()` writing to context
- a tick middleware that increments `cycle_count`
- a transition plus a domain event returned from one `CycleResponse`
- a listener subscribed by event class
- `snapshot()`, `JsonSnapshotSerializer`, and `restore()`

Run it with:

```bash
php examples/simple-workflow/run.php
```

The guided tutorial that walks through this code lives in [docs/getting-started.md](../../docs/getting-started.md).
