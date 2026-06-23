# Testing

Automata is easiest to test at three levels:

## 1. Automaton-Level Tests

Test one automaton in isolation when you want to verify:

- how it reacts to a specific input
- which `CycleResponse` it returns
- how it mutates context

These tests are good for pure state-transition rules.

## 2. Orchestrator Flow Tests

Test `Orchestrator` when you need to verify:

- activation
- middleware order
- transition behavior
- event dispatch after state change
- introspection helpers

The main reference is [tests/Core/OrchestratorTest.php](../tests/Core/OrchestratorTest.php).

## 3. Snapshot Tests

Test snapshot capture and restore when you depend on:

- `contextState`
- `fsmAutomatonId`
- serializable automaton state
- strict restore failures

Reference tests:

- [tests/Core/State/JsonSnapshotSerializerTest.php](../tests/Core/State/JsonSnapshotSerializerTest.php)
- [tests/DTO/StateSnapshotTest.php](../tests/DTO/StateSnapshotTest.php)

## Onboarding Example Test

The beginner example is protected by:

- [tests/Examples/SimpleWorkflowApplicationTest.php](../tests/Examples/SimpleWorkflowApplicationTest.php)

It verifies:

- initial activation
- middleware mutation
- transition from `idle` to `active`
- domain event observation after transition
- snapshot round-trip restore

## Quality Gate

```bash
composer validate
composer stan
composer test
```

Or run the aggregate script:

```bash
composer check
```
