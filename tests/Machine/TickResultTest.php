<?php

declare(strict_types=1);

namespace Automata\Tests\Machine;

use Automata\Machine\CycleResponse;
use Automata\Machine\TickResult;
use Automata\Tests\Support\TestCommand;
use Automata\Tests\Support\TestEvent;
use PHPUnit\Framework\TestCase;

final class TickResultTest extends TestCase
{
    public function testTransitionedAndMessageFiltering(): void
    {
        $event = new TestEvent('e');
        $command = new TestCommand('c');

        $stayed = new TickResult(1, 'a', 'a', CycleResponse::none(), [$command, $event]);
        $moved = new TickResult(2, 'a', 'b', CycleResponse::none(), []);

        self::assertFalse($stayed->transitioned());
        self::assertTrue($moved->transitioned());
        self::assertSame([$event], $stayed->messagesOf(TestEvent::class));
        self::assertSame([$command], $stayed->messagesOf(TestCommand::class));
        self::assertSame([], $moved->messagesOf(TestEvent::class));
    }
}
