# Automata

[![CI](https://github.com/chatflowphp/automata/actions/workflows/ci.yml/badge.svg)](https://github.com/chatflowphp/automata/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-tested-brightgreen.svg)](https://phpunit.de/)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

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

## Start Here

- Build your first automaton: [docs/getting-started.md](docs/getting-started.md)
- Understand orchestrator semantics: [docs/orchestrator.md](docs/orchestrator.md), [docs/commands-events.md](docs/commands-events.md), [docs/snapshots.md](docs/snapshots.md)
- Browse extension and reference docs: [docs/context.md](docs/context.md), [docs/extending.md](docs/extending.md), [docs/testing.md](docs/testing.md)

The full documentation hub lives at [docs/index.md](docs/index.md).

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

For the guided beginner path, use [docs/getting-started.md](docs/getting-started.md) and the runnable [simple-workflow](examples/simple-workflow/README.md) example.

## Reading Order

1. [Documentation Hub](docs/index.md)
2. [Getting Started](docs/getting-started.md)
3. [Orchestrator](docs/orchestrator.md)
4. [Commands And Events](docs/commands-events.md)
5. [Snapshots](docs/snapshots.md)
6. [Context](docs/context.md)
7. [Extending](docs/extending.md)
8. [Testing](docs/testing.md)

## Examples

### Simple Workflow

`simple-workflow` is the shortest runnable example:

```bash
php examples/simple-workflow/run.php
```

It demonstrates:

- `Orchestrator`
- `ArrayContext`
- two automata
- middleware
- one transition command
- one domain event
- one listener
- snapshot and restore

### Traffic Light

`traffic-light` is the advanced canonical demo:

```bash
php examples/traffic-light/run.php
```

It demonstrates:

- three automata connected by transitions
- middleware-driven tick counting
- domain events observed after transitions
- lifecycle events
- snapshot save, restore, and resumed execution

Read [examples/traffic-light/README.md](examples/traffic-light/README.md) after the beginner path.

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
