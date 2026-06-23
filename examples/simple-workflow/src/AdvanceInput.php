<?php

declare(strict_types=1);

namespace AutomataExamples\SimpleWorkflow;

use Automata\Contracts\InputInterface;

final class AdvanceInput implements InputInterface
{
    public function __construct(private readonly string $reason)
    {
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
