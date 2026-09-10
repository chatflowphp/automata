# Getting Started

This guide builds the [survey-bot example](../examples/survey-bot/README.md): a bot that asks for a
name and an age, confirms, and remembers where each chat is between requests.

## 1. Install

```bash
composer require chatflowphp/automata
```

PHP 8.1 or newer. The only runtime dependency is `psr/clock`.

## 2. Define the input and the reply

Every tick receives one input. For a bot that is the message text:

```php
final class IncomingMessage implements InputInterface
{
    public function __construct(public readonly string $text) {}
}
```

States talk back by emitting an event. The transport layer decides how to deliver it:

```php
final class BotReply implements EventInterface
{
    public function __construct(public readonly string $text) {}
}
```

## 3. Write the states

Extend `AbstractState`, declare the input class in `INPUT`, and implement `handle()`.
`onEnter()` runs when the state becomes current, which is the natural place to ask the question:

```php
/** @extends AbstractState<IncomingMessage> */
final class AskNameState extends AbstractState
{
    public const ID = 'survey.ask_name';
    protected const INPUT = IncomingMessage::class;

    public function getId(): string
    {
        return self::ID;
    }

    public function onEnter(ContextInterface $context): CycleResponse
    {
        return CycleResponse::fromEvent(new BotReply('Hi! What is your name?'));
    }

    protected function handle(InputInterface $input, ContextInterface $context): CycleResponse
    {
        $name = trim($input->text);

        if ($name === '') {
            return CycleResponse::fromEvent(new BotReply('Please tell me your name.'));
        }

        $context->set('name', $name);

        return CycleResponse::transitionTo(AskAgeState::ID);
    }
}
```

Returning `transitionTo()` moves the machine to the next state, whose `onEnter()` asks the next
question in the same tick. See [States](states.md).

## 4. Declare the allowed transitions

A `TransitionTable` makes illegal jumps impossible and documents the flow. Guards are closures
over the context:

```php
$transitions = TransitionTable::define([
    AskNameState::ID => [AskAgeState::ID => fn (ContextInterface $c): bool => $c->getString('name') !== ''],
    AskAgeState::ID  => [ConfirmState::ID => fn (ContextInterface $c): bool => $c->getInt('age') > 0],
    ConfirmState::ID => [DoneState::ID, AskNameState::ID],
    DoneState::ID    => [AskNameState::ID],
]);
```

`$transitions->toMermaid()` renders the graph for your README. See [Transitions](transitions.md).

## 5. Assemble the machine

```php
$machine = new StateMachine(new ArrayContext(), transitions: $transitions);
$machine->registerStates(new AskNameState(), new AskAgeState(), new ConfirmState(), new DoneState());
$machine->subscribe(BotReply::class, $replyCollector);
```

Listeners are subscribed by message class and run after the tick commits, so they always see
the final state. See [Messaging](messaging.md).

## 6. Handle one request

A webhook handler resumes the chat from a `SnapshotStoreInterface`, applies the message, and
persists the result:

```php
$session = Session::resume($store, 'chat:' . $chatId, fn () => $this->buildMachine($replies), AskNameState::ID);

if (!$session->isNew()) {
    $session->tick(new IncomingMessage($text));
}

$session->persist();

return $replies->all();
```

A new chat is started in `survey.ask_name`; its `onEnter()` reply is the greeting. Every later
request restores the snapshot, ticks once, and saves. See [Sessions](sessions.md).

## 7. Run and test

```bash
php examples/survey-bot/run.php
composer test
```

The example test drives a full conversation through an `InMemorySnapshotStore` and a `FrozenClock`.
See [Testing](testing.md).
