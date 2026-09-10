# Sessions

Most flows do not keep a `StateMachine` in memory. A request arrives, the flow is restored, one
input is applied, and the result is stored. `Session` and `SnapshotStoreInterface` package that
cycle.

## SnapshotStoreInterface

```php
interface SnapshotStoreInterface
{
    public function load(string $key): ?StateSnapshot;
    public function save(string $key, StateSnapshot $snapshot): void;
    public function delete(string $key): void;
}
```

`InMemorySnapshotStore` ships with the library for tests and single-process workers. The
survey-bot example contains a `FileSnapshotStore`; a production store typically wraps a database
table keyed by conversation id and uses `JsonSnapshotSerializer` for the payload.

## Session

```php
$session = Session::resume($store, 'chat:' . $chatId, $factory, AskNameState::ID);

if (!$session->isNew()) {
    $result = $session->tick(new IncomingMessage($text));
}

$session->persist();
```

- `resume()` calls the factory, which must return a machine that is not started, with every state
  and listener registered.
- When the store has a snapshot for the key, the machine is restored from it.
- Otherwise the machine is started in the initial state and `isNew()` returns `true`. The
  `onEnter()` output of the initial state has already been dispatched to the bus at this point,
  so subscribe your reply collector inside the factory.
- `tick()` delegates to the machine; `persist()` saves a fresh snapshot; `end()` deletes the key.

## Persisting from middleware

An alternative to `persist()` after every request is a middleware that saves inside the tick:

```php
final class PersistMiddleware implements TickMiddlewareInterface
{
    public function handle(CycleRequest $request, callable $next): TickResult
    {
        $result = $next($request);
        $this->store->save($this->key, $this->machine->snapshot());

        return $result;
    }
}
```

`snapshot()` after `$next()` sees the committed tick. If `save()` throws, the tick rolls back and
no listener runs, which keeps the store and the machine consistent. See [Middleware](middleware.md).

## Expiry

Snapshots carry `createdAt`. Compare it to the current time when loading to expire abandoned
conversations, then delete the key and start over.
