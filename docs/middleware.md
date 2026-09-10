# Middleware

`Automata\Middleware\TickMiddlewareInterface` wraps a whole tick:

```php
interface TickMiddlewareInterface
{
    /** @param callable(CycleRequest): TickResult $next */
    public function handle(CycleRequest $request, callable $next): TickResult;
}
```

`$next()` runs the inner middleware, `process()`, every transition, and the collection of emitted
messages, and returns the `TickResult`. Messages are dispatched to the bus only after the outermost
middleware returns.

## Order

Middleware run in registration order, each wrapping the next:

```
outer.before ─► inner.before ─► process() ─► inner.after ─► outer.after ─► dispatch
```

## What a middleware can do

| Goal | How |
| --- | --- |
| Count or time ticks | Mutate the context before or after `$next()` |
| Replace the input | `$next($request->withInput($other))` |
| Wrap in a database transaction | Begin before `$next()`, commit after, throw to roll back |
| Persist a snapshot | Call `StateMachine::snapshot()` after `$next()`, see [Sessions](sessions.md) |
| Veto the tick | Throw; the machine rolls back and nothing is dispatched |
| Trigger a transition | Call `StateMachine::transitionTo()`; it becomes part of the tick |
| Inspect the outcome | Read `TickResult` before returning it |

A middleware must not call `tick()`; that throws `ReentrantTickException`.

## Example

```php
final class CycleCounterMiddleware implements TickMiddlewareInterface
{
    public function handle(CycleRequest $request, callable $next): TickResult
    {
        $request->getContext()->increment('cycle_count');

        return $next($request);
    }
}
```
