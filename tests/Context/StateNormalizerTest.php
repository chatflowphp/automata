<?php

declare(strict_types=1);

namespace Automata\Tests\Context;

use Automata\Context\StateNormalizer;
use PHPUnit\Framework\TestCase;

final class StateNormalizerTest extends TestCase
{
    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function keys(): iterable
    {
        yield 'zero' => ['0', true];
        yield 'positive' => ['42', true];
        yield 'negative' => ['-1', true];
        yield 'leading zero' => ['01', false];
        yield 'float-like' => ['1.5', false];
        yield 'word' => ['abc', false];
        yield 'empty' => ['', false];
    }

    /**
     * @dataProvider keys
     */
    public function testIsNumericKeyMatchesPhpKeyCoercion(string $key, bool $expected): void
    {
        self::assertSame($expected, StateNormalizer::isNumericKey($key));
        $array = [$key => true];

        self::assertSame($expected, array_key_first($array) !== $key);
    }

    public function testNestedListsKeepIntegerKeys(): void
    {
        $normalized = StateNormalizer::normalizeMap(['items' => [10 => 'a', 11 => 'b']], 'root');

        self::assertSame(['items' => [10 => 'a', 11 => 'b']], $normalized);
    }
}
