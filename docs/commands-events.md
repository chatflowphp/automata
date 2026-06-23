# Commands And Events

Automata exchange runtime output through messages carried in `CycleResponse`.

## Message Types

| Type | Interface | Meaning |
| --- | --- | --- |
| Command | `CommandInterface` | Request that something should happen |
| Event | `EventInterface` | Describe that something already happened |
| Transition command | `TransitionCommandInterface` | Special command that changes the active automaton |

Use commands for actions. Use events for observations.

## `CycleResponse`

`CycleResponse` is the immutable result object returned by `AutomatonInterface::process()`.

### Factories

| Method | Purpose |
| --- | --- |
| `CycleResponse::none()` | Return no commands and no events |
| `fromCommand()` | Start with one command |
| `fromCommands()` | Start with many commands |
| `fromEvent()` | Start with one event |
| `fromEvents()` | Start with many events |
| `transitionTo()` | Convenience helper that creates a built-in `TransitionCommand` |

### Builders

| Method | Purpose |
| --- | --- |
| `withCommand()` | Append one command |
| `withCommands()` | Append many commands |
| `withEvent()` | Append one event |
| `withEvents()` | Append many events |
| `merge()` | Merge another `CycleResponse` into the current one |

## Common Patterns

Return nothing:

```php
return CycleResponse::none();
```

Return one transition command:

```php
return CycleResponse::fromCommand(new TransitionCommand(ActiveState::ID));
```

Return a command plus a domain event:

```php
return CycleResponse::fromCommand(new TransitionCommand(ActiveState::ID))
    ->withEvent(new WorkflowAdvancedEvent('idle', 'active'));
```

## Dispatch Semantics

For each completed `tick()` the orchestrator processes the returned `CycleResponse` in this order:

1. Commands are processed in order
2. If a command implements `TransitionCommandInterface`, the transition is applied first
3. The command itself is then dispatched on the message bus
4. Events are dispatched in order
5. `CycleCompletedEvent` is emitted last

This means domain event listeners observe the final active automaton and final context state for the tick.

## Built-In Transition Command

The library ships `Automata\Messages\TransitionCommand`.

You can use it directly:

```php
new TransitionCommand('workflow.active')
```

Or via the convenience helper:

```php
CycleResponse::transitionTo('workflow.active');
```

`NoopCommand` is a special internal helper that is ignored by dispatch.
