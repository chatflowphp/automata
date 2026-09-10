<?php

declare(strict_types=1);

namespace Automata\Tests\Support;

use Automata\Messaging\CommandInterface;

final class TestCommand implements CommandInterface
{
    public function __construct(public readonly string $label) {}
}
