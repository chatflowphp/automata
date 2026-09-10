<?php

declare(strict_types=1);

namespace Automata\Tests\Machine\Transition;

use Automata\Context\ArrayContext;
use Automata\Context\ContextInterface;
use Automata\Machine\Transition\AllowAllTransitions;
use Automata\Machine\Transition\TransitionTable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TransitionTableTest extends TestCase
{
    public function testDefineWithPlainTargetsAndGuards(): void
    {
        $table = TransitionTable::define([
            'ask_name' => ['ask_age'],
            'ask_age' => ['confirm' => static fn(ContextInterface $context): bool => $context->getInt('age') > 0],
            'confirm' => ['done', 'ask_name'],
        ]);

        $context = new ArrayContext();

        self::assertTrue($table->isAllowed('ask_name', 'ask_age', $context));
        self::assertFalse($table->isAllowed('ask_name', 'confirm', $context));
        self::assertFalse($table->isAllowed('unknown', 'ask_age', $context));

        self::assertFalse($table->isAllowed('ask_age', 'confirm', $context));
        $context->set('age', 30);
        self::assertTrue($table->isAllowed('ask_age', 'confirm', $context));

        self::assertSame(['done', 'ask_name'], $table->targetsFrom('confirm'));
        self::assertSame([], $table->targetsFrom('done'));
        self::assertTrue($table->isGuarded('ask_age', 'confirm'));
        self::assertFalse($table->isGuarded('ask_name', 'ask_age'));
        self::assertFalse($table->isGuarded('ask_name', 'nowhere'));
        self::assertSame([
            'ask_name' => ['ask_age'],
            'ask_age' => ['confirm'],
            'confirm' => ['done', 'ask_name'],
        ], $table->edges());
    }

    public function testAllowIsFluent(): void
    {
        $table = (new TransitionTable())
            ->allow('a', 'b')
            ->allow('b', 'a', static fn(ContextInterface $context): bool => $context->getBool('back'));

        $context = new ArrayContext(['back' => true]);

        self::assertTrue($table->isAllowed('a', 'b', $context));
        self::assertTrue($table->isAllowed('b', 'a', $context));
    }

    public function testInvalidDefinitionIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transition definition for "a" must list target ids or map target id => guard.');

        TransitionTable::define(['a' => [static fn(ContextInterface $context): bool => true]]);
    }

    public function testMermaidRendering(): void
    {
        $table = TransitionTable::define([
            'flow.start' => ['flow.end' => static fn(ContextInterface $context): bool => true],
            'flow.end' => ['flow.start'],
        ]);

        $expected = <<<'MERMAID'
            stateDiagram-v2
                state "flow.start" as flow_start
                state "flow.end" as flow_end
                flow_start --> flow_end : guarded
                flow_end --> flow_start

            MERMAID;

        self::assertSame($expected, $table->toMermaid());
    }

    public function testAllowAllPolicyAcceptsEverything(): void
    {
        $policy = new AllowAllTransitions();

        self::assertTrue($policy->isAllowed('a', 'b', new ArrayContext()));
    }
}
