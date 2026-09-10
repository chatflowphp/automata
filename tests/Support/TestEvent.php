<?php

declare(strict_types=1);

namespace Automata\Tests\Support;

use Automata\Messaging\EventInterface;

final class TestEvent implements EventInterface
{
    public function __construct(public readonly string $label) {}
}
