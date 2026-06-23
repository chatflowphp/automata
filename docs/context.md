# Context

`Automata\Contracts\ContextInterface` is the shared state container available to all automata during processing.

## Contract

The public contract is small:

```php
$context->get('key', $default);
$context->set('key', $value);
$context->getState();
$context->setState($state);
```

Use context for cross-automaton state that must survive activation changes.

## Default Implementation

`Automata\Core\Context\ArrayContext` is the default implementation:

```php
$context = new ArrayContext([
    'status' => 'idle',
]);
```

It is suitable for examples, tests, and any runtime that can manage persistence outside the library.

## State Shape Rules

Context state must be serializable.

Allowed values:

- `null`
- `bool`
- `int`
- `float`
- `string`
- arrays composed from the same allowed values
- `BackedEnum`, normalized to its scalar value

Rejected values:

- non-backed enums
- arbitrary objects
- resources

## Context vs Automaton Internal State

| Use | Put It In | Why |
| --- | --- | --- |
| Shared workflow facts | `ContextInterface` | Multiple automata and listeners may need to read or update it |
| Automaton-specific restorable data | `SerializableAutomatonInterface` state | The data belongs to one automaton and should be snapshot-aware |
| Derived output only | Neither, unless it must survive a cycle | Recompute it when needed |

## Snapshot Relationship

`snapshot()` always includes:

- `contextState`
- `fsmAutomatonId`

It includes automaton internal state only for automata that implement `SerializableAutomatonInterface`.

For restore details, read [Snapshots](snapshots.md).
