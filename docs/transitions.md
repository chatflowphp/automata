# Transitions

A transition happens when a `TransitionCommandInterface` is applied, either returned from
`process()` or `onEnter()`, or requested explicitly with `StateMachine::transitionTo()`.

```php
return CycleResponse::transitionTo(ConfirmState::ID);          // built-in TransitionCommand
return CycleResponse::fromCommand(new ChangeColorCommand(...)); // your own command that implements TransitionCommandInterface
```

## Policies

Every transition between two different states is checked against a `TransitionPolicyInterface`:

```php
interface TransitionPolicyInterface
{
    public function isAllowed(string $fromStateId, string $toStateId, ContextInterface $context): bool;
}
```

The default `AllowAllTransitions` accepts everything, which suits prototypes and flows that route
dynamically. A rejected transition throws `InvalidTransitionException` and rolls the operation back.

## TransitionTable

Declare the graph once and the runtime enforces it:

```php
$table = TransitionTable::define([
    'survey.ask_name' => ['survey.ask_age' => fn (ContextInterface $c): bool => $c->getString('name') !== ''],
    'survey.ask_age'  => ['survey.confirm' => fn (ContextInterface $c): bool => $c->getInt('age') > 0],
    'survey.confirm'  => ['survey.done', 'survey.ask_name'],
]);

$machine = new StateMachine($context, transitions: $table);
```

A target listed as a plain string is always allowed; a target mapped to a closure is allowed only
while the guard returns `true`. The fluent form does the same:

```php
$table = (new TransitionTable())
    ->allow('a', 'b')
    ->allow('b', 'a', fn (ContextInterface $c): bool => $c->getBool('can_go_back'));
```

## Introspection

```php
$machine->canTransitionTo('survey.done');   // registered and allowed right now; true for the current state
$machine->getAllowedTransitions();          // list<string>, excludes the current state

$table->targetsFrom('survey.confirm');      // declared targets, guards ignored
$table->isGuarded('survey.ask_age', 'survey.confirm');
$table->edges();                            // array<string, list<string>>
```

## Diagrams

```php
echo $table->toMermaid();
```

```
stateDiagram-v2
    state "survey.ask_name" as survey_ask_name
    state "survey.ask_age" as survey_ask_age
    survey_ask_name --> survey_ask_age : guarded
```

Paste the output into a Markdown file that renders Mermaid.

## Rules

- A transition to the current state is a no-op and is never checked.
- Transitions chain: `onEnter()` may transition again. More than 32 transitions in one operation throw.
- The policy runs before `onLeave()`, so a rejected transition leaves no trace.
