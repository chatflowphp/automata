<?php

declare(strict_types=1);

namespace Automata\Tests\State;

use Automata\Context\ArrayContext;
use Automata\Context\ContextInterface;
use Automata\Exception\UnexpectedInputException;
use Automata\Machine\CycleRequest;
use Automata\Machine\CycleResponse;
use Automata\Machine\InputInterface;
use Automata\State\AbstractState;
use Automata\Tests\Support\OtherInput;
use Automata\Tests\Support\TestEvent;
use Automata\Tests\Support\TestInput;
use PHPUnit\Framework\TestCase;

final class AbstractStateTest extends TestCase
{
    public function testHandleReceivesTypedInputAndContext(): void
    {
        $state = new EchoState();
        $context = new ArrayContext();

        $response = $state->process(new CycleRequest(new TestInput('hello'), $context));

        self::assertSame('hello', $context->getString('echo'));
        self::assertCount(1, $response->getEvents());
    }

    public function testUnexpectedInputIsRejectedBeforeHandle(): void
    {
        $state = new EchoState();

        $this->expectException(UnexpectedInputException::class);
        $this->expectExceptionMessage(\sprintf(
            'State "echo" expects input of type %s, got %s.',
            TestInput::class,
            OtherInput::class,
        ));

        $state->process(new CycleRequest(new OtherInput(), new ArrayContext()));
    }

    public function testDefaultHooksDoNothing(): void
    {
        $state = new EchoState();
        $context = new ArrayContext();

        self::assertTrue($state->onEnter($context)->isEmpty());
        $state->onLeave($context);
        self::assertSame([], $context->getState());
    }
}

/**
 * @extends AbstractState<TestInput>
 */
final class EchoState extends AbstractState
{
    protected const INPUT = TestInput::class;

    public function getId(): string
    {
        return 'echo';
    }

    protected function handle(InputInterface $input, ContextInterface $context): CycleResponse
    {
        $context->set('echo', $input->text);

        return CycleResponse::fromEvent(new TestEvent($input->text));
    }
}
