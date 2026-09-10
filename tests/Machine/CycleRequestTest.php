<?php

declare(strict_types=1);

namespace Automata\Tests\Machine;

use Automata\Context\ArrayContext;
use Automata\Machine\CycleRequest;
use Automata\Tests\Support\OtherInput;
use Automata\Tests\Support\TestInput;
use PHPUnit\Framework\TestCase;

final class CycleRequestTest extends TestCase
{
    public function testWithInputKeepsContextAndReturnsNewInstance(): void
    {
        $context = new ArrayContext();
        $input = new TestInput('a');
        $request = new CycleRequest($input, $context);

        $replacement = new OtherInput();
        $changed = $request->withInput($replacement);

        self::assertNotSame($request, $changed);
        self::assertSame($input, $request->getInput());
        self::assertSame($replacement, $changed->getInput());
        self::assertSame($context, $changed->getContext());
    }
}
