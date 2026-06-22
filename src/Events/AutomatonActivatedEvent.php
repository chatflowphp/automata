<?php

declare(strict_types=1);

namespace Automata\Events;

use Automata\Contracts\EventInterface;

/**
 * Emitted after an automaton becomes active.
 */
final class AutomatonActivatedEvent implements EventInterface
{
    public const NAME = 'automata.automaton.activated';

    public function __construct(
        private readonly string $automatonId,
        private readonly ?string $previousAutomatonId
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array{automaton_id:string, previous_automaton_id:string|null}
     */
    public function getPayload(): array
    {
        return [
            'automaton_id' => $this->automatonId,
            'previous_automaton_id' => $this->previousAutomatonId,
        ];
    }

    public function getAutomatonId(): string
    {
        return $this->automatonId;
    }

    public function getPreviousAutomatonId(): ?string
    {
        return $this->previousAutomatonId;
    }
}
