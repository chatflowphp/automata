<?php

declare(strict_types=1);

namespace AutomataExamples\SimpleWorkflow;

use Automata\Machine\InputInterface;

final class AdvanceInput implements InputInterface
{
    public function __construct(public readonly string $reason) {}
}
