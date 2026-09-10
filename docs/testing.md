# Testing

## Levels

| Level | What to assert | Tools |
| --- | --- | --- |
| State | The `CycleResponse` returned for a given input and context | `new CycleRequest($input, new ArrayContext([...]))`, call `process()` directly |
| Machine | Transition order, listener output, rollback, `TickResult` | `StateMachine` with `InMemory` everything |
| Flow | A whole conversation across requests | `Session`, `InMemorySnapshotStore`, `FrozenClock` |

## Deterministic time

Inject `Automata\Clock\FrozenClock` wherever a `ClockInterface` is accepted:

```php
$clock = FrozenClock::at('2026-09-10T12:00:00+00:00');
$machine = new StateMachine($context, clock: $clock);
$clock->advance('+1 hour');
```

## Observing messages

Subscribe an invokable object that records what it receives, or use `TickResult::messagesOf()`:

```php
$result = $machine->tick($input);
$replies = array_map(fn (BotReply $r): string => $r->text, $result->messagesOf(BotReply::class));
```

## Reference tests in this repository

- [tests/Machine/StateMachineTest.php](../tests/Machine/StateMachineTest.php): every runtime rule
- [tests/Examples/SurveyBotTest.php](../tests/Examples/SurveyBotTest.php): a flow across requests
- [tests/Snapshot/JsonSnapshotSerializerTest.php](../tests/Snapshot/JsonSnapshotSerializerTest.php): migrations

## Quality gate

```bash
composer check      # composer validate, php-cs-fixer, phpstan, phpunit
composer cs:fix     # apply code style
```

CI runs the tests on PHP 8.1 through 8.4, static analysis on 8.3, and mutation testing with
Infection.
