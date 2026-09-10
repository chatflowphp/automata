<?php

declare(strict_types=1);

namespace Automata\Tests\Support;

use Automata\Machine\InputInterface;

final class TestInput implements InputInterface
{
    public function __construct(public readonly string $text = '') {}
}
