# Getting Started

Build your first automaton with the runnable [simple-workflow](../examples/simple-workflow/README.md) example.

By the end of this guide you will have:

- one `Orchestrator`
- one `ArrayContext`
- two automata: `idle` and `active`
- one middleware that increments `cycle_count`
- one domain event listener
- one snapshot and restore round-trip

Reference files:

- [SimpleWorkflowApplication.php](../examples/simple-workflow/src/Application/SimpleWorkflowApplication.php)
- [IdleState.php](../examples/simple-workflow/src/States/IdleState.php)
- [ActiveState.php](../examples/simple-workflow/src/States/ActiveState.php)
- [CycleCounterMiddleware.php](../examples/simple-workflow/src/Middleware/CycleCounterMiddleware.php)
- [SimpleWorkflowApplicationTest.php](../tests/Examples/SimpleWorkflowApplicationTest.php)

## 1. Install The Package

```bash
composer require chatflowphp/automata
```

## 2. Create Shared Context And The Orchestrator

Use `ArrayContext` as the default in-memory implementation of `ContextInterface`:

```php
$context = new ArrayContext([
    'cycle_count' => 0,
    'workflow_status' => 'not_started',
]);

$orchestrator = new Orchestrator($context);
```

Deeper reference: [Context](context.md)

## 3. Implement Two Automata

Implement `AutomatonInterface` and give each automaton a stable id:

```php
final class IdleState implements AutomatonInterface
{
    public const ID = 'workflow.idle';

    public function getId(): string
    {
        return self::ID;
    }
}
```

In the example:

- `IdleState` handles the first input and requests a transition
- `ActiveState` becomes the final active automaton and returns `CycleResponse::none()`

Deeper reference: [Orchestrator](orchestrator.md)

## 4. Return A Transition Command And Domain Event

Use `CycleResponse::fromCommand()` to request a transition and `withEvent()` to emit a domain event from the same cycle:

```php
return CycleResponse::fromCommand(new TransitionCommand(ActiveState::ID))
    ->withEvent(new WorkflowAdvancedEvent('idle', 'active'));
```

This is the core runtime pattern:

- commands request actions
- transition commands change the active automaton
- events describe what already happened

Deeper reference: [Commands And Events](commands-events.md)

## 5. Add One Middleware

Middleware wraps each `tick()` and can mutate shared context before the active automaton processes the request:

```php
final class CycleCounterMiddleware implements CycleMiddlewareInterface
{
    public function handle(CycleRequest $request, callable $next): CycleResponse
    {
        $count = $request->getContext()->get('cycle_count', 0);
        $request->getContext()->set('cycle_count', $count + 1);

        return $next($request);
    }
}
```

Deeper reference: [Extending](extending.md)

## 6. Subscribe To A Domain Event

Register a listener directly on the orchestrator:

```php
$orchestrator->subscribe(WorkflowAdvancedEvent::NAME, function (WorkflowAdvancedEvent $event): void {
    // react to the already-applied transition
});
```

In the example, the listener appends a human-readable line to `transition_log` in context.

## 7. Activate And Tick

Start the workflow with `activate()` and advance it with `tick()`:

```php
$orchestrator->activate(IdleState::ID);
$orchestrator->tick(new AdvanceInput('activate-workflow'));
```

After the first tick in the example:

- middleware increments `cycle_count`
- `IdleState` reads the input and returns a transition command plus event
- the orchestrator switches to `ActiveState`
- the event listener sees the already-updated active state

Deeper reference: [Orchestrator](orchestrator.md)

## 8. Snapshot And Restore

Capture a snapshot after processing:

```php
$snapshot = $orchestrator->snapshot();
```

Restore it into a fresh application:

```php
$restored = new SimpleWorkflowApplication();
$restored->getOrchestrator()->activateFromSnapshot($snapshot);
```

Deeper reference: [Snapshots](snapshots.md)

## 9. Run The Example

```bash
php examples/simple-workflow/run.php
```

Then inspect the advanced demo:

```bash
php examples/traffic-light/run.php
```
