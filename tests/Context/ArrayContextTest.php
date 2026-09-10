<?php

declare(strict_types=1);

namespace Automata\Tests\Context;

use Automata\Context\ArrayContext;
use Automata\Exception\ContextTypeException;
use Automata\Exception\InvalidStateValueException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ArrayContextTest extends TestCase
{
    public function testBasicAccessors(): void
    {
        $context = new ArrayContext(['a' => 1]);

        self::assertTrue($context->has('a'));
        self::assertFalse($context->has('b'));
        self::assertSame(1, $context->get('a'));
        self::assertSame('fallback', $context->get('b', 'fallback'));

        $context->set('b', null);
        self::assertTrue($context->has('b'));
        self::assertNull($context->get('b', 'fallback'));

        $context->remove('a');
        self::assertFalse($context->has('a'));
        self::assertSame(['b' => null], $context->getState());
    }

    public function testTypedGettersReturnDefaultsForMissingOrNullKeys(): void
    {
        $context = new ArrayContext(['nothing' => null]);

        self::assertSame(7, $context->getInt('missing', 7));
        self::assertSame(7, $context->getInt('nothing', 7));
        self::assertSame(1.5, $context->getFloat('missing', 1.5));
        self::assertSame('x', $context->getString('missing', 'x'));
        self::assertTrue($context->getBool('missing', true));
        self::assertSame(['d'], $context->getArray('missing', ['d']));
        self::assertSame([], $context->getList('missing'));
    }

    public function testTypedGettersReturnStoredValues(): void
    {
        $context = new ArrayContext([
            'int' => 3,
            'float' => 2.5,
            'string' => 's',
            'bool' => false,
            'map' => ['k' => 'v'],
            'list' => ['a', 'b'],
        ]);

        self::assertSame(3, $context->getInt('int'));
        self::assertSame(2.5, $context->getFloat('float'));
        self::assertSame(3.0, $context->getFloat('int'));
        self::assertSame('s', $context->getString('string'));
        self::assertFalse($context->getBool('bool', true));
        self::assertSame(['k' => 'v'], $context->getArray('map'));
        self::assertSame(['a', 'b'], $context->getList('list'));
    }

    /**
     * @return iterable<string, array{string, mixed, string}>
     */
    public static function wrongTypes(): iterable
    {
        yield 'int from string' => ['getInt', 'nope', 'int'];
        yield 'float from string' => ['getFloat', 'nope', 'float'];
        yield 'string from int' => ['getString', 1, 'string'];
        yield 'bool from int' => ['getBool', 1, 'bool'];
        yield 'array from string' => ['getArray', 'nope', 'array'];
        yield 'list from map' => ['getList', ['k' => 'v'], 'list'];
    }

    /**
     * @dataProvider wrongTypes
     */
    public function testTypedGettersRejectOtherTypes(string $method, mixed $stored, string $expected): void
    {
        $context = new ArrayContext(['key' => $stored]);

        $this->expectException(ContextTypeException::class);
        $this->expectExceptionMessage(\sprintf('Context key "key" is expected to hold %s, got %s.', $expected, get_debug_type($stored)));

        match ($method) {
            'getInt' => $context->getInt('key'),
            'getFloat' => $context->getFloat('key'),
            'getString' => $context->getString('key'),
            'getBool' => $context->getBool('key'),
            'getArray' => $context->getArray('key'),
            default => $context->getList('key'),
        };
    }

    public function testPushCreatesAndAppendsToLists(): void
    {
        $context = new ArrayContext();

        $context->push('log', 'one');
        $context->push('log', 'two');

        self::assertSame(['one', 'two'], $context->getList('log'));
    }

    public function testIncrementStartsFromZeroAndReturnsNewValue(): void
    {
        $context = new ArrayContext();

        self::assertSame(1, $context->increment('n'));
        self::assertSame(3, $context->increment('n', 2));
        self::assertSame(3, $context->getInt('n'));
    }

    public function testBackedEnumsAreNormalizedToScalarsOnWrite(): void
    {
        $context = new ArrayContext(['status' => ContextBackedEnum::READY]);

        self::assertSame('ready', $context->get('status'));

        $context->set('nested', ['enum' => ContextBackedEnum::READY]);

        self::assertSame(['enum' => 'ready'], $context->get('nested'));
    }

    public function testNonBackedEnumsAreRejected(): void
    {
        $context = new ArrayContext();

        $this->expectException(InvalidStateValueException::class);
        $this->expectExceptionMessage('Invalid state at "context.status": non-backed enums are not supported.');

        $context->set('status', ContextPureEnum::READY);
    }

    public function testObjectsAreRejected(): void
    {
        $context = new ArrayContext();

        $this->expectException(InvalidStateValueException::class);
        $this->expectExceptionMessage('Invalid state at "context.obj": value must be scalar, array, backed enum, or null, got stdClass.');

        $context->set('obj', new stdClass());
    }

    public function testNumericStringKeysAreRejectedOnSet(): void
    {
        $context = new ArrayContext();

        $this->expectException(InvalidStateValueException::class);
        $this->expectExceptionMessage('Invalid context key "0"');

        $context->set('0', 'x');
    }

    public function testIntegerKeysAreRejectedOnConstruction(): void
    {
        $this->expectException(InvalidStateValueException::class);
        $this->expectExceptionMessage('Invalid state at "context": keys must be non-numeric strings, got 0.');

        new ArrayContext(['x']);
    }

    public function testSetStateReplacesEverything(): void
    {
        $context = new ArrayContext(['old' => 1]);

        $context->setState(['new' => ContextBackedEnum::READY]);

        self::assertSame(['new' => 'ready'], $context->getState());
    }
}

enum ContextBackedEnum: string
{
    case READY = 'ready';
}

enum ContextPureEnum
{
    case READY;
}
