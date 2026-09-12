# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project adheres to
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [2.0.0-rc1] - 2026-09-12

A rewrite. See [docs/upgrade-from-1.x.md](docs/upgrade-from-1.x.md) for the full mapping.

### Added

- `StateMachine::transitionTo()`, `canTransitionTo()`, `getAllowedTransitions()`, `getStateIds()`, `getTickCount()`
- `TickResult` returned by `tick()` with the emitted messages
- `AbstractState` with a typed `INPUT` constant and PHPStan generics
- `TransitionTable` with guards and Mermaid export; `TransitionPolicyInterface`
- `ResumableStateInterface::onResume()`
- Typed context accessors: `getInt`, `getFloat`, `getString`, `getBool`, `getArray`, `getList`, `push`, `increment`, plus `has` and `remove`
- Snapshot `schemaVersion`, `tickCount`, `createdAt`; `SnapshotMigrationInterface`; `LegacyV1Migration`
- `SnapshotStoreInterface`, `InMemorySnapshotStore`, `Session`
- `SystemClock`, `FrozenClock`
- `survey-bot` example; tests for every example
- CI matrix for PHP 8.1 to 8.4, php-cs-fixer, PHPStan strict rules, Infection

### Changed

- **Breaking:** `Automaton` is now `State`, `Orchestrator` is now `StateMachine`; all namespaces reorganised
- **Breaking:** `onEnter()` returns a `CycleResponse`
- **Breaking:** message bus routes by class; `MessageInterface`, `getName()`, `getPayload()`, `AbstractCommand`, `NoopCommand` removed
- **Breaking:** `TransitionCommandInterface` extends `CommandInterface`
- **Breaking:** middleware wraps the whole tick and returns `TickResult`
- **Breaking:** `restore()` replaces `activateFromSnapshot()`, works only on a fresh machine, emits nothing
- **Breaking:** `start()` replaces `activate()` and throws on a started machine
- Operations are atomic: rollback on exception, dispatch after commit, re-entrant `tick()` throws
- Duplicate state ids throw instead of overwriting
- Exceptions extend `RuntimeException` through `AutomataException`

### Fixed

- Floats no longer degrade to integers through JSON snapshots
- Context keys that PHP coerces to integers are rejected on write

## [1.0.2] - 2026-06-23

### Added

- Documentation set and the simple-workflow onboarding example

## [1.0.1] - 2026-06-23

### Changed

- Package status badges

## [1.0.0] - 2026-06-23

### Added

- Initial release

[Unreleased]: https://github.com/chatflowphp/automata/compare/2.0.0...HEAD
[2.0.0]: https://github.com/chatflowphp/automata/compare/1.0.2...2.0.0
[1.0.2]: https://github.com/chatflowphp/automata/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/chatflowphp/automata/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/chatflowphp/automata/releases/tag/1.0.0
