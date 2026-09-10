# Messaging

States communicate through plain objects. Two marker interfaces classify them:

| Interface | Meaning | Dispatched |
| --- | --- | --- |
| `CommandInterface` | Ask for something to happen | Once, in order, after any transition it carries |
| `EventInterface` | Report something that happened | Once, in order, after all commands |
| `TransitionCommandInterface` | A command that also changes the current state | After the transition is applied |

Messages carry data as typed properties. There is no name or payload contract.

## CycleResponse

The immutable result of `process()` and `onEnter()`:

| Method | Purpose |
| --- | --- |
| `CycleResponse::none()` | Nothing to do |
| `fromCommand()`, `fromCommands()` | Start with commands |
| `fromEvent()`, `fromEvents()` | Start with events |
| `transitionTo(id)` | A built-in `TransitionCommand` |
| `withCommand()`, `withCommands()` | Append commands |
| `withEvent()`, `withEvents()` | Append events |
| `withTransitionTo(id)` | Append a built-in transition |
| `merge(other)` | Append everything from another response |
| `isEmpty()`, `getCommands()`, `getEvents()` | Inspection |

Elements are validated on construction; a non-command or non-event throws
`InvalidCycleResponseException`.

## The message bus

`MessageBusInterface` routes by class:

```php
$machine->subscribe(BotReply::class, $collector);                  // one class
$machine->subscribe(EventInterface::class, $auditLog);             // every event
$machine->subscribe(TransitionCommandInterface::class, $tracer);   // every transition command
```

A handler receives every message that is an instance of the subscribed class, so the callable can
declare the concrete type. Handlers run synchronously in this order: exact class, parent classes,
interfaces; within a type, subscription order.

The default `MessageBus` is in-memory. Pass your own implementation to the `StateMachine`
constructor to bridge into another dispatcher.

## Built-in events

| Event | Emitted |
| --- | --- |
| `StateActivated(stateId, previousStateId)` | After `start()` and after every transition; not on `restore()` |
| `TransitionApplied(fromStateId, toStateId)` | After every transition, before `StateActivated` |
| `TickCompleted(request, result)` | Last message of every tick |

## Dispatch timing

Nothing reaches the bus while an operation runs. Messages are queued and dispatched after the
operation commits, in the order they were emitted. Listeners therefore see the final state of the
machine, and a failed operation dispatches nothing. See [State Machine](state-machine.md).
