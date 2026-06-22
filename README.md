# Automata

[![CI](https://github.com/chatflowphp/automata/workflows/CI/badge.svg)](https://github.com/chatflowphp/automata/actions)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-tested-brightgreen.svg)](https://phpunit.de/)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)

Framework-agnostic orchestration engine for single-active finite state machines in PHP.

## What It Does

- Activates one automaton at a time and drives it through discrete `tick()` cycles
- Wraps each cycle with optional middleware
- Dispatches commands and events through a lightweight in-memory message bus
- Emits lifecycle events for activation and applied transitions
- Supports snapshot/restore for shared context and serializable automata state

## What It Does Not Do

- No statechart semantics
- No parallel or multi-active automata runtime
- No built-in persistence transport or async messaging backend

## Requirements

- PHP 8.1 or newer
- Composer

## Installation

```bash
composer require chatflowphp/automata
```

## Quick Start

```php
<?php

use Automata\Contracts\AutomatonInterface;
use Automata\Contracts\ContextInterface;
use Automata\Contracts\InputInterface;
use Automata\Core\Context\ArrayContext;
use Automata\Core\CycleRequest;
use Automata\Core\CycleResponse;
use Automata\Core\Orchestrator;

$context = new ArrayContext();

$automaton = new class implements AutomatonInterface {
    public function getId(): string
    {
        return 'demo';
    }

    public function onEnter(ContextInterface $context): void
    {
        $context->set('status', 'idle');
    }

    public function process(CycleRequest $request): CycleResponse
    {
        $request->getContext()->set('status', 'processed');

        return CycleResponse::none();
    }

    public function onLeave(ContextInterface $context): void
    {
    }
};

$orchestrator = new Orchestrator($context);
$orchestrator->registerAutomaton($automaton);
$orchestrator->activate('demo');
$orchestrator->tick(new class implements InputInterface {});
```

## Lifecycle

- `activate()` resolves the target automaton and calls `onEnter()`
- `tick()` runs middleware in registration order and then calls `process()` on the active automaton
- Transition commands call `onLeave()` on the current automaton and `onEnter()` on the next one
- Self-transition is a no-op for lifecycle hooks
- `AutomatonActivatedEvent` is emitted after activation
- `TransitionAppliedEvent` is emitted after a real transition changes the active automaton

Use the introspection helpers when needed:

- `getActiveAutomatonId(): ?string`
- `hasActiveAutomaton(): bool`
- `hasAutomaton(string $id): bool`

## Transition And Dispatch Semantics

For each completed `tick()` the orchestrator applies results in this order:

1. Process all commands returned by `CycleResponse`
2. Apply transitions before publishing transition commands to the message bus
3. Dispatch lifecycle events for applied transitions and activation
4. Dispatch domain events from `CycleResponse`
5. Dispatch `CycleCompletedEvent` last

This means event listeners observe the final active automaton and final context state for the tick.

## Snapshot Semantics

Capture state:

```php
$snapshot = $orchestrator->snapshot();
```

Restore state:

```php
$restoredContext = new ArrayContext();
$restoredOrchestrator = new Orchestrator($restoredContext);
$restoredOrchestrator->registerAutomaton($automaton);
$restoredOrchestrator->activateFromSnapshot($snapshot);
```

Serialize externally:

```php
use Automata\Core\State\JsonSnapshotSerializer;

$serializer = new JsonSnapshotSerializer();
$payload = $serializer->serialize($snapshot);
$restored = $serializer->deserialize($payload);
```

Snapshot restore is strict:

- missing active automaton id causes an exception
- missing registered automata state handlers cause an exception
- invalid snapshot structure causes an exception

## State Serialization Rules

Context state and serializable automata state must contain only:

- `null`
- `bool`
- `int`
- `float`
- `string`
- arrays composed from the same allowed values
- `BackedEnum`, which is normalized to its scalar value

Non-backed enums and arbitrary objects are rejected.

## Extension Points

- `CycleMiddlewareInterface` for per-tick cross-cutting behavior
- `MessageBusInterface` for custom message delivery
- `SerializableAutomatonInterface` for automata that need snapshot support
- `SnapshotSerializerInterface` for custom snapshot wire formats
- `TransitionCommandInterface` for custom transition commands

## Traffic Light Example

The `examples/traffic-light` directory contains a CLI demo that shows:

- three automata connected by transitions
- middleware-driven tick counting
- direct domain-event listeners observing state after transitions
- snapshot save/restore through `StateSnapshot`

Run it with:

```bash
php examples/traffic-light/run.php
```

## Testing

```bash
composer test
```

Full local quality gate:

```bash
composer check
```

## License

This project is released under the MIT License. See `LICENSE` for details.
