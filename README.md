# Automata

[![CI](https://github.com/chatflowphp/automata/actions/workflows/ci.yml/badge.svg)](https://github.com/chatflowphp/automata/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg)](https://phpstan.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](composer.json)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

A framework-agnostic state machine runtime for flows that advance one input at a time and must
survive between requests: chat bots, multi-step forms, approval workflows.

```php
$session = Session::resume($store, 'chat:' . $chatId, fn () => $this->buildMachine($replies), AskNameState::ID);

if (!$session->isNew()) {
    $session->tick(new IncomingMessage($text));
}

$session->persist();

return $replies->all();   // ['Nice to meet you, Alice. How old are you?']
```

## What you get

- **States as classes.** Extend `AbstractState`, declare the input type, implement `handle()`.
  `onEnter()` can reply or chain into the next state.
- **Declared transitions.** A `TransitionTable` with guards rejects illegal jumps and renders as a
  Mermaid diagram. Or allow everything and route dynamically.
- **Atomic ticks.** Context, current state, and state data roll back on any exception. Listeners
  run after commit and never see a half-applied tick.
- **Typed messaging.** Commands and events are plain objects; subscribe by class, including
  interfaces for wildcards.
- **Versioned snapshots.** JSON with a schema version, migrations, tick counter, and timestamp.
  `Session` plus `SnapshotStoreInterface` give you the request cycle in three lines.
- **Middleware around the whole tick**, for transactions, persistence, or tracing.

## What it is not

- Not a statechart engine: no parallel regions, no history states.
- Not a message transport: the bus is synchronous and in-process.
- Not a persistence layer: bring your own `SnapshotStoreInterface`.

## Install

```bash
composer require chatflowphp/automata
```

PHP 8.1 or newer. The only runtime dependency is `psr/clock`.

## A state

```php
/** @extends AbstractState<IncomingMessage> */
final class AskAgeState extends AbstractState
{
    public const ID = 'survey.ask_age';
    protected const INPUT = IncomingMessage::class;

    public function getId(): string
    {
        return self::ID;
    }

    public function onEnter(ContextInterface $context): CycleResponse
    {
        return CycleResponse::fromEvent(new BotReply(sprintf('Nice to meet you, %s. How old are you?', $context->getString('name'))));
    }

    protected function handle(InputInterface $input, ContextInterface $context): CycleResponse
    {
        if (!ctype_digit($input->text)) {
            return CycleResponse::fromEvent(new BotReply('Please enter a number.'));
        }

        $context->set('age', (int) $input->text);

        return CycleResponse::transitionTo(ConfirmState::ID);
    }
}
```

## A machine

```php
$machine = new StateMachine(new ArrayContext(), transitions: TransitionTable::define([
    AskNameState::ID => [AskAgeState::ID],
    AskAgeState::ID  => [ConfirmState::ID => fn (ContextInterface $c): bool => $c->getInt('age') > 0],
    ConfirmState::ID => [DoneState::ID, AskNameState::ID],
]));

$machine->registerStates(new AskNameState(), new AskAgeState(), new ConfirmState(), new DoneState());
$machine->subscribe(BotReply::class, $replies);

$machine->start(AskNameState::ID);          // "Hi! What is your name?"
$result = $machine->tick(new IncomingMessage('Alice'));

$result->toStateId;                          // survey.ask_age
$result->messagesOf(BotReply::class);        // the question asked by the new state
```

## Documentation

Start at the [documentation hub](docs/index.md):

- [Getting Started](docs/getting-started.md), the survey bot step by step
- [State Machine](docs/state-machine.md), [States](docs/states.md), [Transitions](docs/transitions.md)
- [Messaging](docs/messaging.md), [Context](docs/context.md), [Middleware](docs/middleware.md)
- [Snapshots](docs/snapshots.md), [Sessions](docs/sessions.md), [Testing](docs/testing.md)
- [Upgrade from 1.x](docs/upgrade-from-1.x.md)

## Examples

| Example | Shows |
| --- | --- |
| [survey-bot](examples/survey-bot/README.md) | The canonical chat flow: sessions, snapshot store, guards, replies from `onEnter()` |
| [simple-workflow](examples/simple-workflow/README.md) | The smallest machine: two states, middleware, one event, snapshot round trip |
| [traffic-light](examples/traffic-light/README.md) | Declared transition graph, shared state base class, injected clock, resumed execution |

```bash
php examples/survey-bot/run.php
```

## Development

```bash
composer check   # validate, code style, phpstan, tests
```

## Versioning

2.0 is a rewrite of 1.x with a new API. See [CHANGELOG.md](CHANGELOG.md) and the
[upgrade guide](docs/upgrade-from-1.x.md). Snapshots written by 1.x can be migrated.

## License

MIT, see [LICENSE](LICENSE).
