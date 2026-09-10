<?php

declare(strict_types=1);

namespace Automata\State;

use Automata\Context\ContextInterface;
use Automata\Exception\UnexpectedInputException;
use Automata\Machine\CycleRequest;
use Automata\Machine\CycleResponse;
use Automata\Machine\InputInterface;

/**
 * Base class that removes the boilerplate of a typical state.
 *
 * Declare the accepted input class in the INPUT constant and implement handle(). Inputs of another
 * class are rejected with UnexpectedInputException before handle() runs. Lifecycle hooks default to
 * doing nothing.
 *
 * @template TInput of InputInterface
 */
abstract class AbstractState implements StateInterface
{
    /**
     * @var class-string<InputInterface>
     */
    protected const INPUT = InputInterface::class;

    public function onEnter(ContextInterface $context): CycleResponse
    {
        return CycleResponse::none();
    }

    public function onLeave(ContextInterface $context): void {}

    final public function process(CycleRequest $request): CycleResponse
    {
        $input = $request->getInput();

        if (!is_a($input, static::INPUT)) {
            throw UnexpectedInputException::create($this, static::INPUT, $input);
        }

        /** @var TInput $input */
        return $this->handle($input, $request->getContext());
    }

    /**
     * @param TInput $input
     */
    abstract protected function handle(InputInterface $input, ContextInterface $context): CycleResponse;
}
