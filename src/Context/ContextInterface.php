<?php

declare(strict_types=1);

namespace Automata\Context;

use Automata\Exception\ContextTypeException;
use Automata\Exception\InvalidStateValueException;

/**
 * Shared, snapshot-safe key/value state visible to every state, middleware, and listener.
 *
 * Values must be null, bool, int, float, string, backed enums, or arrays of those.
 * Typed accessors return the default when the key is missing or null and throw
 * ContextTypeException when the stored value has another type.
 */
interface ContextInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function has(string $key): bool;

    /**
     * @throws InvalidStateValueException When the value is not snapshot-safe or the key is numeric.
     */
    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;

    /**
     * @throws ContextTypeException
     */
    public function getInt(string $key, int $default = 0): int;

    /**
     * @throws ContextTypeException
     */
    public function getFloat(string $key, float $default = 0.0): float;

    /**
     * @throws ContextTypeException
     */
    public function getString(string $key, string $default = ''): string;

    /**
     * @throws ContextTypeException
     */
    public function getBool(string $key, bool $default = false): bool;

    /**
     * @param array<array-key, mixed> $default
     *
     * @return array<array-key, mixed>
     *
     * @throws ContextTypeException
     */
    public function getArray(string $key, array $default = []): array;

    /**
     * @return list<mixed>
     *
     * @throws ContextTypeException
     */
    public function getList(string $key): array;

    /**
     * Appends a value to the list stored under the key, creating the list when missing.
     *
     * @throws ContextTypeException
     */
    public function push(string $key, mixed $value): void;

    /**
     * Adds to the integer stored under the key, treating a missing key as zero. Returns the new value.
     *
     * @throws ContextTypeException
     */
    public function increment(string $key, int $by = 1): int;

    /**
     * @return array<string, mixed>
     */
    public function getState(): array;

    /**
     * @param array<string, mixed> $state
     *
     * @throws InvalidStateValueException
     */
    public function setState(array $state): void;
}
