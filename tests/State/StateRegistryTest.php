<?php

declare(strict_types=1);

namespace Automata\Tests\State;

use Automata\Exception\DuplicateStateException;
use Automata\Exception\StateNotFoundException;
use Automata\State\StateRegistry;
use Automata\Tests\Support\RecordingState;
use PHPUnit\Framework\TestCase;

final class StateRegistryTest extends TestCase
{
    public function testRegisterGetHasAll(): void
    {
        $registry = new StateRegistry();
        $a = new RecordingState('a');
        $b = new RecordingState('b');

        $registry->register($a);
        $registry->register($b);

        self::assertTrue($registry->has('a'));
        self::assertFalse($registry->has('c'));
        self::assertSame($a, $registry->get('a'));
        self::assertSame(['a' => $a, 'b' => $b], $registry->all());
    }

    public function testDuplicateIdIsRejected(): void
    {
        $registry = new StateRegistry();
        $registry->register(new RecordingState('a'));

        $this->expectException(DuplicateStateException::class);
        $this->expectExceptionMessage('State "a" is already registered. Use replace() to override it.');

        $registry->register(new RecordingState('a'));
    }

    public function testReplaceOverridesExistingState(): void
    {
        $registry = new StateRegistry();
        $first = new RecordingState('a');
        $second = new RecordingState('a');

        $registry->register($first);
        $registry->replace($second);

        self::assertSame($second, $registry->get('a'));
    }

    public function testGetUnknownIdThrows(): void
    {
        $registry = new StateRegistry();

        $this->expectException(StateNotFoundException::class);
        $this->expectExceptionMessage('State "missing" is not registered.');

        $registry->get('missing');
    }
}
