<?php

declare(strict_types=1);

namespace Automata\Core;

use Automata\Contracts\ContextInterface;
use Automata\Contracts\InputInterface;

/**
 * Immutable descriptor of a processing cycle that carries the input and shared context.
 */
final class CycleRequest
{
    public function __construct(
        private readonly InputInterface $input,
        private readonly ContextInterface $context
    ) {
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function withInput(InputInterface $input): self
    {
        return new self($input, $this->context);
    }

    public function getContext(): ContextInterface
    {
        return $this->context;
    }
}
