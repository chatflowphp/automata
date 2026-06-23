# Simple Workflow Example

This is the shortest runnable example in the repository.

It demonstrates:

- `Orchestrator`
- `ArrayContext`
- two automata: `idle` and `active`
- one middleware that increments `cycle_count`
- one transition command returned through `CycleResponse::fromCommand()`
- one domain event appended through `withEvent()`
- one listener registered through `Orchestrator::subscribe()`
- one snapshot and restore round-trip

Run it with:

```bash
php examples/simple-workflow/run.php
```

If you want the guided tutorial, start with [docs/getting-started.md](../../docs/getting-started.md).
