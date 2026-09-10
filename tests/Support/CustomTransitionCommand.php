<?php

declare(strict_types=1);

namespace Automata\Tests\Support;

use Automata\Messaging\TransitionCommandInterface;

final class CustomTransitionCommand implements TransitionCommandInterface
{
    public function __construct(
        public readonly string $targetStateId,
        public readonly string $label = 'custom',
    ) {}

    public function getTargetStateId(): string
    {
        return $this->targetStateId;
    }
}
