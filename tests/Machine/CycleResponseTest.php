<?php

declare(strict_types=1);

namespace Automata\Tests\Machine;

use Automata\Exception\InvalidCycleResponseException;
use Automata\Machine\CycleResponse;
use Automata\Messaging\TransitionCommand;
use Automata\Tests\Support\TestCommand;
use Automata\Tests\Support\TestEvent;
use PHPUnit\Framework\TestCase;
use stdClass;

final class CycleResponseTest extends TestCase
{
    public function testNoneIsEmpty(): void
    {
        $response = CycleResponse::none();

        self::assertTrue($response->isEmpty());
        self::assertSame([], $response->getCommands());
        self::assertSame([], $response->getEvents());
    }

    public function testFactoriesAndBuildersPreserveOrder(): void
    {
        $c1 = new TestCommand('1');
        $c2 = new TestCommand('2');
        $c3 = new TestCommand('3');
        $e1 = new TestEvent('1');
        $e2 = new TestEvent('2');
        $e3 = new TestEvent('3');

        $response = CycleResponse::fromCommand($c1)
            ->withCommands(['x' => $c2])
            ->withCommand($c3)
            ->withEvent($e1)
            ->withEvents([5 => $e2, 9 => $e3]);

        self::assertSame([$c1, $c2, $c3], $response->getCommands());
        self::assertSame([$e1, $e2, $e3], $response->getEvents());
        self::assertFalse($response->isEmpty());

        self::assertSame([$c1, $c2], CycleResponse::fromCommands(['a' => $c1, 'b' => $c2])->getCommands());
        self::assertSame([$e1], CycleResponse::fromEvent($e1)->getEvents());
        self::assertSame([$e1, $e2], CycleResponse::fromEvents([$e1, $e2])->getEvents());
    }

    public function testTransitionHelpersCreateBuiltInCommand(): void
    {
        $commands = CycleResponse::transitionTo('next')->getCommands();

        self::assertCount(1, $commands);
        self::assertInstanceOf(TransitionCommand::class, $commands[0]);
        self::assertSame('next', $commands[0]->getTargetStateId());

        $chained = CycleResponse::fromEvent(new TestEvent('e'))->withTransitionTo('other')->getCommands();

        self::assertInstanceOf(TransitionCommand::class, $chained[0]);
        self::assertSame('other', $chained[0]->targetStateId);
    }

    public function testMergeAppendsOtherResponse(): void
    {
        $first = CycleResponse::fromCommand(new TestCommand('a'))->withEvent(new TestEvent('a'));
        $second = CycleResponse::fromCommand(new TestCommand('b'))->withEvent(new TestEvent('b'));

        $merged = $first->merge($second);

        self::assertSame(['a', 'b'], array_map(static fn(object $c): string => $c instanceof TestCommand ? $c->label : '?', $merged->getCommands()));
        self::assertSame(['a', 'b'], array_map(static fn(object $e): string => $e instanceof TestEvent ? $e->label : '?', $merged->getEvents()));
        self::assertCount(1, $first->getCommands());
    }

    public function testBuildersReturnNewInstances(): void
    {
        $original = CycleResponse::none();
        $changed = $original->withEvent(new TestEvent('e'));

        self::assertNotSame($original, $changed);
        self::assertTrue($original->isEmpty());
    }

    public function testNonCommandsAreRejected(): void
    {
        $this->expectException(InvalidCycleResponseException::class);
        $this->expectExceptionMessage('CycleResponse expects instances of Automata\Messaging\CommandInterface, got stdClass.');

        new CycleResponse([new stdClass()]);
    }

    public function testNonEventsAreRejected(): void
    {
        $this->expectException(InvalidCycleResponseException::class);
        $this->expectExceptionMessage('CycleResponse expects instances of Automata\Messaging\EventInterface, got string.');

        new CycleResponse([], ['nope']);
    }
}
