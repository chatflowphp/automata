# Snapshots

Snapshots capture orchestrator state so a workflow can be restored later.

## What `snapshot()` Includes

`Orchestrator::snapshot()` returns `StateSnapshot` with:

- `contextState`
- `fsmAutomatonId`
- `automataStates`

`automataStates` includes only automata that implement `SerializableAutomatonInterface`.

## Capture State

```php
$snapshot = $orchestrator->snapshot();
```

Use `toArray()` or `JsonSnapshotSerializer` when you need a wire format.

## Restore State

```php
$orchestrator->activateFromSnapshot($snapshot);
```

Restore behavior is strict:

- the active automaton id must be registered
- every automaton state entry must point to a registered automaton
- every automaton state entry must target an automaton that implements `SerializableAutomatonInterface`
- invalid snapshot structure throws `SnapshotHydrationException`

## Important Restore Semantics

`activateFromSnapshot()` does this:

1. validate the snapshot
2. restore `contextState`
3. restore serializable automaton state
4. set the active automaton id
5. emit lifecycle activation events

It does **not** call `onEnter()` during restore. The snapshot is treated as the source of truth for already-restored state.

## JSON Serialization

Use `JsonSnapshotSerializer` for external persistence:

```php
use Automata\Core\State\JsonSnapshotSerializer;

$serializer = new JsonSnapshotSerializer();
$payload = $serializer->serialize($snapshot);
$restored = $serializer->deserialize($payload);
```

The serializer expects a JSON object root and throws `SnapshotHydrationException` for invalid payloads.

## Serializable Automata

If an automaton has internal state that must survive restore, implement `SerializableAutomatonInterface`:

```php
interface SerializableAutomatonInterface extends AutomatonInterface
{
    public function getState(): array;
    public function setState(array $state): void;
}
```

Use this for automaton-owned state. Use `ContextInterface` for shared workflow state.
