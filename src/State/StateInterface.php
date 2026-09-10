<?php

declare(strict_types=1);

namespace Automata\State;

use Automata\Context\ContextInterface;
use Automata\Machine\CycleRequest;
use Automata\Machine\CycleResponse;

/**
 * A state of the machine. Exactly one state is current at any time.
 *
 * Lifecycle: onEnter() when the state becomes current, process() once per tick while it is current,
 * onLeave() right before another state becomes current.
 */
interface StateInterface
{
    /**
     * Stable identifier unique within one state machine.
     */
    public function getId(): string;

    /**
     * Called when the state becomes current, both on start() and after a transition.
     *
     * The returned response is applied like a tick response: commands are dispatched, a transition
     * command chains into the next state, events are emitted. Return CycleResponse::none() when
     * nothing needs to happen.
     */
    public function onEnter(ContextInterface $context): CycleResponse;

    /**
     * Handles one input while the state is current.
     */
    public function process(CycleRequest $request): CycleResponse;

    /**
     * Called before another state becomes current. Not called on restore() or when the machine is discarded.
     */
    public function onLeave(ContextInterface $context): void;
}
