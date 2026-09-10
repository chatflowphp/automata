<?php

declare(strict_types=1);

namespace Automata\Context;

use Automata\Exception\ContextTypeException;

/**
 * Implements the typed accessors of ContextInterface on top of get(), has(), and set().
 */
trait ContextAccessorsTrait
{
    abstract public function get(string $key, mixed $default = null): mixed;

    abstract public function set(string $key, mixed $value): void;

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        if (!\is_int($value)) {
            throw ContextTypeException::forKey($key, 'int', $value);
        }

        return $value;
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        if (!\is_int($value) && !\is_float($value)) {
            throw ContextTypeException::forKey($key, 'float', $value);
        }

        return (float) $value;
    }

    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        if (!\is_string($value)) {
            throw ContextTypeException::forKey($key, 'string', $value);
        }

        return $value;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        if (!\is_bool($value)) {
            throw ContextTypeException::forKey($key, 'bool', $value);
        }

        return $value;
    }

    public function getArray(string $key, array $default = []): array
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        if (!\is_array($value)) {
            throw ContextTypeException::forKey($key, 'array', $value);
        }

        return $value;
    }

    public function getList(string $key): array
    {
        $value = $this->get($key);

        if ($value === null) {
            return [];
        }

        if (!\is_array($value) || !array_is_list($value)) {
            throw ContextTypeException::forKey($key, 'list', $value);
        }

        return $value;
    }

    public function push(string $key, mixed $value): void
    {
        $list = $this->getList($key);
        $list[] = $value;

        $this->set($key, $list);
    }

    public function increment(string $key, int $by = 1): int
    {
        $value = $this->getInt($key) + $by;

        $this->set($key, $value);

        return $value;
    }
}
