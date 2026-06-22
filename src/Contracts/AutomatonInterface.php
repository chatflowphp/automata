<?php

declare(strict_types=1);

namespace Automata\Contracts;

use Automata\Core\CycleRequest;
use Automata\Core\CycleResponse;

/**
 * Fundamental contract for all automata managed by the orchestrator.
 *
 * Implementations encapsulate business logic that reacts to inputs, manipulates shared context,
 * and emit commands that the orchestrator will dispatch through the message bus.
 */
interface AutomatonInterface
{
    /**
     * Retrieves the unique human-readable identifier of the automaton.
     *
     * @return string Stable identifier that differentiates the automaton instance from others.
     */
    public function getId(): string;

    /**
     * Lifecycle hook invoked when the orchestrator activates the automaton.
     *
     * Typical responsibilities include initializing context defaults or scheduling initial messages.
     *
     * @param ContextInterface $context Shared context accessible across automata.
     *
     * @return void
     */
    public function onEnter(ContextInterface $context): void;

    /**
     * Processes a single cycle of the automaton given input and context.
     *
     * @return CycleResponse Aggregate of commands and events generated during the processing cycle.
     */
    public function process(CycleRequest $request): CycleResponse;

    /**
     * Lifecycle hook invoked when the orchestrator deactivates the automaton.
     *
     * Implementations can perform cleanup or final message dispatches before suspension.
     *
     * @param ContextInterface $context Shared context accessible across automata.
     *
     * @return void
     */
    public function onLeave(ContextInterface $context): void;
}
