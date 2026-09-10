<?php

declare(strict_types=1);

namespace Automata\Machine;

use Automata\Context\ContextInterface;

/**
 * Input plus shared context for one tick. Immutable; middleware can swap the input with withInput().
 */
final class CycleRequest
{
    public function __construct(
        private readonly InputInterface $input,
        private readonly ContextInterface $context,
    ) {}

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
