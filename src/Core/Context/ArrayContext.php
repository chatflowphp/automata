<?php

declare(strict_types=1);

namespace Automata\Core\Context;

use Automata\Core\State\StateNormalizer;
use Automata\Contracts\ContextInterface;

/**
 * Minimal context implementation backed by an in-memory array.
 *
 * Provides a default alternative to the removed Symfony ParameterBag-based context.
 */
class ArrayContext implements ContextInterface
{
    /**
     * @param array<array-key, mixed> $state
     */
    public function __construct(
        /**
         * @var array<array-key, mixed>
         */
        private array $state = [],
    ) {
        $this->state = StateNormalizer::normalizeArray($this->state, 'context');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->state)) {
            return $default;
        }

        return $this->state[$key];
    }

    public function set(string $key, mixed $value): void
    {
        $this->state[$key] = StateNormalizer::normalizeValue($value, sprintf('context.%s', $key));
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getState(): array
    {
        return $this->state;
    }

    /**
     * @param array<array-key, mixed> $state
     */
    public function setState(array $state): void
    {
        $this->state = StateNormalizer::normalizeArray($state, 'context');
    }
}
