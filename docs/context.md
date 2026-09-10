# Context

`Automata\Context\ContextInterface` is the shared state visible to every state, middleware, guard,
and listener. It is captured in every snapshot and rolled back when an operation fails.

## Contract

```php
$context->get('key', $default);
$context->has('key');
$context->set('key', $value);
$context->remove('key');

$context->getInt('age');                 // 0 when missing or null
$context->getFloat('ratio', 1.0);
$context->getString('name', 'anonymous');
$context->getBool('confirmed');
$context->getArray('options');
$context->getList('log');                // [] when missing
$context->push('log', 'line');           // append to a list
$context->increment('attempts');         // returns the new value

$context->getState();                    // array<string, mixed>
$context->setState($state);
```

Typed accessors return the default when the key is missing or holds `null`, and throw
`ContextTypeException` when the value has a different type. `getFloat()` accepts integers.

## Value rules

Values must survive a JSON round trip:

- `null`, `bool`, `int`, `float`, `string`
- arrays of those, nested as needed
- backed enums, which are stored as their backing value

Anything else throws `InvalidStateValueException` on `set()`, so a snapshot can never fail because
of something stored earlier. Keys must be strings that PHP does not coerce to integers: `'0'` is
rejected, `'01'` and `'user_0'` are fine.

## ArrayContext

`ArrayContext` is the default in-memory implementation. Pass initial values to the constructor:

```php
$context = new ArrayContext(['attempts' => 0]);
```

## Custom contexts

Implement `ContextInterface` when the state lives somewhere else, for example in a session object
of your framework. `ContextAccessorsTrait` provides every typed accessor on top of `get()` and
`set()`, so an implementation only needs `get`, `has`, `set`, `remove`, `getState`, and
`setState`. Apply the same value rules, or reuse `StateNormalizer`.

## Context or state data

| Data | Where |
| --- | --- |
| Answers, counters, flags read by several states or listeners | Context |
| Data owned by exactly one state | `SerializableStateInterface`, see [States](states.md) |
| Values derived from other data | Recompute; do not store |
