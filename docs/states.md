# States

A state is a class implementing `Automata\State\StateInterface`:

```php
interface StateInterface
{
    public function getId(): string;
    public function onEnter(ContextInterface $context): CycleResponse;
    public function process(CycleRequest $request): CycleResponse;
    public function onLeave(ContextInterface $context): void;
}
```

| Hook | When | Returns |
| --- | --- | --- |
| `onEnter()` | The state became current through `start()` or a transition | A response applied like a tick response |
| `process()` | Once per `tick()` while current | The tick response |
| `onLeave()` | Before another state becomes current | Nothing |

`restore()` calls none of these. Implement `ResumableStateInterface::onResume()` when a state must
re-arm something in memory after a restore.

## AbstractState

`AbstractState` removes the boilerplate: hooks default to doing nothing and the input is checked
once, in the base class.

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

    protected function handle(InputInterface $input, ContextInterface $context): CycleResponse
    {
        // $input is an IncomingMessage here, for PHPStan and at runtime
    }
}
```

An input of another class throws `UnexpectedInputException` before `handle()` runs. Leave `INPUT`
at its default to accept any input.

## Emitting from onEnter()

Returning a response from `onEnter()` lets a state announce itself: a bot asks its question, a
workflow step notifies an approver. A transition returned from `onEnter()` chains into the next
state within the same operation, which is how a router state can skip steps that are already done.

## Ids

Ids are plain strings and must be unique within one machine. Dotted names such as `survey.ask_age`
read well in logs and render fine in Mermaid diagrams.

## Serializable states

Prefer the shared context for anything more than one state cares about. When a state owns data
that must survive a snapshot, implement `SerializableStateInterface`:

```php
interface SerializableStateInterface extends StateInterface
{
    /** @return array<string, mixed> */
    public function getState(): array;

    /** @param array<string, mixed> $state */
    public function setState(array $state): void;
}
```

The data follows the same rules as context values, see [Context](context.md). It is included in
snapshots under `stateData` and restored by `restore()`, and it takes part in rollback.
