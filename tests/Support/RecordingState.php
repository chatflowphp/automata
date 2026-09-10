<?php

declare(strict_types=1);

namespace Automata\Tests\Support;

use Automata\Context\ContextInterface;
use Automata\Machine\CycleRequest;
use Automata\Machine\CycleResponse;
use Automata\State\StateInterface;
use Closure;

class RecordingState implements StateInterface
{
    public int $enterCount = 0;

    public int $leaveCount = 0;

    public int $processCount = 0;

    private ?Closure $process;

    private ?Closure $onEnter;

    private ?Closure $onLeave;

    /**
     * @param (callable(CycleRequest): CycleResponse)|null $process
     * @param (callable(ContextInterface): CycleResponse)|null $onEnter
     * @param (callable(ContextInterface): void)|null $onLeave
     */
    public function __construct(
        private readonly string $id,
        ?callable $process = null,
        ?callable $onEnter = null,
        ?callable $onLeave = null,
    ) {
        $this->process = $process === null ? null : Closure::fromCallable($process);
        $this->onEnter = $onEnter === null ? null : Closure::fromCallable($onEnter);
        $this->onLeave = $onLeave === null ? null : Closure::fromCallable($onLeave);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function onEnter(ContextInterface $context): CycleResponse
    {
        $this->enterCount++;

        if ($this->onEnter === null) {
            return CycleResponse::none();
        }

        /** @var CycleResponse $response */
        $response = ($this->onEnter)($context);

        return $response;
    }

    public function process(CycleRequest $request): CycleResponse
    {
        $this->processCount++;

        if ($this->process === null) {
            return CycleResponse::none();
        }

        /** @var CycleResponse $response */
        $response = ($this->process)($request);

        return $response;
    }

    public function onLeave(ContextInterface $context): void
    {
        $this->leaveCount++;

        if ($this->onLeave !== null) {
            ($this->onLeave)($context);
        }
    }
}
