<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\Command;

use Automata\Contracts\TransitionCommandInterface;
use Automata\Messages\AbstractCommand;

final class ChangeColorCommand extends AbstractCommand implements TransitionCommandInterface
{
    public const NAME = 'traffic_light.change_color';

    public function __construct(
        private readonly string $color,
        private readonly string $nextAutomatonId
    ) {
    }

    public function getPayload(): mixed
    {
        return [
            'color' => $this->color,
            'next_automaton_id' => $this->nextAutomatonId,
        ];
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getNextAutomatonId(): string
    {
        return $this->nextAutomatonId;
    }
}
