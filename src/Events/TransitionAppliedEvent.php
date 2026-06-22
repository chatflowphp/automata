<?php

declare(strict_types=1);

namespace Automata\Events;

use Automata\Contracts\EventInterface;

/**
 * Emitted after a transition changes the active automaton.
 */
final class TransitionAppliedEvent implements EventInterface
{
    public const NAME = 'automata.transition.applied';

    public function __construct(
        private readonly string $fromAutomatonId,
        private readonly string $toAutomatonId
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array{from_automaton_id:string, to_automaton_id:string}
     */
    public function getPayload(): array
    {
        return [
            'from_automaton_id' => $this->fromAutomatonId,
            'to_automaton_id' => $this->toAutomatonId,
        ];
    }

    public function getFromAutomatonId(): string
    {
        return $this->fromAutomatonId;
    }

    public function getToAutomatonId(): string
    {
        return $this->toAutomatonId;
    }
}
