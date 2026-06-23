# Automata Docs

`chatflowphp/automata` is a small orchestration runtime for single-active finite state machines.

Use this documentation in three modes:

## Build Your First Automaton

Start here if you are new to the library:

1. [Getting Started](getting-started.md)
2. [simple-workflow example](../examples/simple-workflow/README.md)
3. [Testing](testing.md)

## Understand Orchestrator Semantics

Use these pages when you want exact runtime behavior:

1. [Orchestrator](orchestrator.md)
2. [Commands And Events](commands-events.md)
3. [Snapshots](snapshots.md)
4. [Context](context.md)

## Extend The Runtime

Use these pages when you need custom runtime integrations:

1. [Extending](extending.md)
2. [Snapshots](snapshots.md)
3. [Testing](testing.md)

## Public Surface

The most important public concepts are:

- `Automata\Core\Orchestrator`
- `Automata\Contracts\AutomatonInterface`
- `Automata\Contracts\ContextInterface`
- `Automata\Core\Context\ArrayContext`
- `Automata\Core\CycleRequest`
- `Automata\Core\CycleResponse`
- `Automata\DTO\StateSnapshot`
- `Automata\Core\State\JsonSnapshotSerializer`

For the advanced example, see [traffic-light](../examples/traffic-light/README.md).
