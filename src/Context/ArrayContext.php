<?php

declare(strict_types=1);

namespace Automata\Context;

use Automata\Exception\InvalidStateValueException;

/**
 * Default in-memory context. Values are normalized on write so that snapshot() can never fail
 * because of something stored earlier.
 */
class ArrayContext implements ContextInterface
{
    use ContextAccessorsTrait;

    /**
     * @var array<string, mixed>
     */
    private array $state = [];

    /**
     * @param array<array-key, mixed> $state
     */
    public function __construct(array $state = [])
    {
        $this->state = StateNormalizer::normalizeMap($state, 'context');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!\array_key_exists($key, $this->state)) {
            return $default;
        }

        return $this->state[$key];
    }

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->state);
    }

    public function set(string $key, mixed $value): void
    {
        if (StateNormalizer::isNumericKey($key)) {
            throw new InvalidStateValueException(\sprintf(
                'Invalid context key "%s": numeric strings are converted to integers by PHP and cannot be snapshotted.',
                $key,
            ));
        }

        $this->state[$key] = StateNormalizer::normalizeValue($value, 'context.' . $key);
    }

    public function remove(string $key): void
    {
        unset($this->state[$key]);
    }

    public function getState(): array
    {
        return $this->state;
    }

    public function setState(array $state): void
    {
        $this->state = StateNormalizer::normalizeMap($state, 'context');
    }
}
