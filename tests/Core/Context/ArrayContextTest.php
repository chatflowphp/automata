<?php

declare(strict_types=1);

namespace Automata\Tests\Core\Context;

use Automata\Core\Context\ArrayContext;
use Automata\Exceptions\SnapshotHydrationException;
use PHPUnit\Framework\TestCase;

final class ArrayContextTest extends TestCase
{
    public function testBackedEnumsAreNormalizedToScalars(): void
    {
        $context = new ArrayContext(['status' => ContextBackedEnum::READY]);

        self::assertSame('ready', $context->get('status'));

        $context->set('nested', ['enum' => ContextBackedEnum::READY]);

        self::assertSame(['enum' => 'ready'], $context->get('nested'));
    }

    public function testNonBackedEnumsAreRejected(): void
    {
        $context = new ArrayContext();

        $this->expectException(SnapshotHydrationException::class);
        $this->expectExceptionMessage('non-backed enums are not supported');

        $context->set('status', ContextPureEnum::READY);
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
