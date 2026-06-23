# Extending

Most users only need:

- `Orchestrator`
- `ArrayContext`
- `AutomatonInterface`
- `CycleResponse`
- listeners registered through `subscribe()`

Use the extension points below only when the default runtime model is not enough.

## Middleware

Implement `CycleMiddlewareInterface` to wrap each `tick()`:

```php
final class MyMiddleware implements CycleMiddlewareInterface
{
    public function handle(CycleRequest $request, callable $next): CycleResponse
    {
        return $next($request);
    }
}
```

Typical uses:

- counters
- tracing
- access control
- request shaping
- response augmentation

## Custom Message Bus

`Orchestrator` accepts a custom `MessageBusInterface` in the constructor.

Use this when you need:

- custom synchronous routing
- integration with another in-process event system
- instrumentation around dispatch

The default `MessageBus` is synchronous and in-memory.

## Serializable Automata

Implement `SerializableAutomatonInterface` when an automaton owns state that must be included in snapshots:

```php
final class MyAutomaton implements SerializableAutomatonInterface
{
    public function getState(): array
    {
        return [];
    }

    public function setState(array $state): void
    {
    }
}
```

Do not move shared workflow state into automaton state without a reason. Prefer `ContextInterface` for shared data.

## Custom Snapshot Serializers

`SnapshotSerializerInterface` lets you define another wire format:

```php
interface SnapshotSerializerInterface
{
    public function serialize(StateSnapshot $snapshot): string;
    public function deserialize(string $payload): StateSnapshot;
}
```

Use this when JSON is not the right transport format.

## Normal Usage vs Extension Points

| Need | Use |
| --- | --- |
| Build a workflow with a few automata | default `Orchestrator`, `ArrayContext`, `CycleResponse` |
| Add per-cycle cross-cutting behavior | `CycleMiddlewareInterface` |
| Persist automaton-owned internal state | `SerializableAutomatonInterface` |
| Change message delivery behavior | `MessageBusInterface` |
| Change snapshot wire format | `SnapshotSerializerInterface` |
