# Snapshots

`StateMachine::snapshot()` returns an immutable `StateSnapshot`:

| Field | Content |
| --- | --- |
| `schemaVersion` | `StateSnapshot::SCHEMA_VERSION`, currently 2 |
| `createdAt` | Time from the machine's clock, ISO-8601 |
| `tickCount` | Number of committed ticks |
| `currentStateId` | Current state id or `null` for a machine that was never started |
| `contextState` | `ContextInterface::getState()` |
| `stateData` | `getState()` of every `SerializableStateInterface` state, keyed by id |

`toArray()` and `jsonSerialize()` produce the array form; `StateSnapshot::fromArray()` validates
it strictly and throws `SnapshotHydrationException` on any problem. Snapshots cannot be built with
invalid data, so `restore()` never needs to re-validate.

## Restore

```php
$machine->restore($snapshot);
```

- Only on a machine that was not started; otherwise `IllegalStateException`.
- Every state referenced by the snapshot must be registered, and states with data in `stateData`
  must implement `SerializableStateInterface`; otherwise `SnapshotHydrationException`.
- Sets the context, the state data, the tick counter, and the current state.
- Calls `onResume()` on the current state if it implements `ResumableStateInterface`.
- Calls no other hook and emits no event. A restored machine looks exactly like it did when the
  snapshot was taken, without side effects.

## JSON

```php
$serializer = new JsonSnapshotSerializer();
$json = $serializer->serialize($snapshot);
$snapshot = $serializer->deserialize($json);
```

Floats keep their type through the round trip. Pass extra `json_encode` flags with
`new JsonSnapshotSerializer(encodeFlags: JSON_PRETTY_PRINT)`.

## Versioning and migrations

`schemaVersion` lets you change what a snapshot contains without breaking persisted sessions.
When you rename a context key or restructure state data:

1. Bump the version you write, for example by wrapping the serializer, or keep `SCHEMA_VERSION`
   when the library format is unchanged and version your own keys inside `contextState`.
2. Register a migration for every older version still in your store:

```php
final class RenameNickname implements SnapshotMigrationInterface
{
    public function fromVersion(): int { return 2; }

    public function migrate(array $raw): array
    {
        $raw['contextState']['name'] = $raw['contextState']['nickname'] ?? '';
        unset($raw['contextState']['nickname']);
        $raw['schemaVersion'] = 3;

        return $raw;
    }
}

$serializer = new JsonSnapshotSerializer([new RenameNickname()]);
```

`deserialize()` reads `schemaVersion`, treats a missing field as version 1, and applies
migrations in sequence until `StateSnapshot::SCHEMA_VERSION` is reached. A gap in the chain or a
migration that does not raise the version throws `SnapshotHydrationException`.

## Snapshots written by 1.x

`Automata\Snapshot\Migration\LegacyV1Migration` converts the 1.x shape
(`fsmAutomatonId`, `automataStates`, no version) into version 2:

```php
$serializer = new JsonSnapshotSerializer([new LegacyV1Migration()]);
```

Legacy snapshots get `tickCount` 0 and `createdAt` set to the migration time.
