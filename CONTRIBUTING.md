# Contributing to Automata

Thank you for your interest in contributing.

## Development setup

```bash
git clone https://github.com/chatflowphp/automata.git
cd automata
composer install
```

## Quality gate

Run everything CI runs:

```bash
composer check
```

Individually:

```bash
composer cs        # code style (php-cs-fixer, dry run)
composer cs:fix    # apply code style
composer stan      # PHPStan, level max with strict rules
composer test      # PHPUnit
```

CI also runs the tests on PHP 8.1 through 8.4 and mutation testing with Infection.

## Guidelines

- Every behaviour change comes with a test.
- Keep the runtime free of dependencies beyond `psr/clock`.
- Public API changes go into `CHANGELOG.md` under Unreleased.
- Code style is PER-CS 2.0 with `declare(strict_types=1)`; `composer cs:fix` applies it.

## Submitting changes

1. Fork the repository and create a branch.
2. Make your changes and run `composer check`.
3. Open a pull request describing the motivation and the behaviour change.

## Bug reports

Include the PHP version, the library version, a minimal reproduction, and the full exception message.

## License

By contributing, you agree that your contributions are licensed under the MIT License.
