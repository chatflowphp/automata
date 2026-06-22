# Contributing to Automata

Thank you for your interest in contributing to Automata! This document provides guidelines for contributors.

## Development Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/chatflowphp/automata.git
   cd automata
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

## Running Tests

Run the test suite:
```bash
vendor/bin/phpunit
```

## Static Analysis

Run PHPStan for static analysis:
```bash
vendor/bin/phpstan analyse
```

## Code Style

This project follows PSR-12 coding standards. Please ensure your code complies with these standards.

## Submitting Changes

1. Fork the repository
2. Create a feature branch: `git checkout -b feature-name`
3. Make your changes and ensure tests pass
4. Commit your changes: `git commit -am 'Add some feature'`
5. Push to the branch: `git push origin feature-name`
6. Submit a pull request

## Bug Reports

When filing bug reports, please include:
- PHP version
- Library version
- A minimal reproduction case
- Any error messages or stack traces

## Feature Requests

Feature requests are welcome! Please provide a clear description of the feature you'd like to see and why it would be useful.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
