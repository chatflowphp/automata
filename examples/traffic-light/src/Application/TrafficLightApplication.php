<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\Application;

use Automata\Clock\SystemClock;
use Automata\Context\ArrayContext;
use Automata\Events\TickCompleted;
use Automata\Machine\StateMachine;
use Automata\Machine\Transition\TransitionTable;
use AutomataExamples\TrafficLight\Enum\TrafficLightStatus;
use AutomataExamples\TrafficLight\Event\LightColorChanged;
use AutomataExamples\TrafficLight\Listener\ChangeColorListener;
use AutomataExamples\TrafficLight\Listener\TickReporter;
use AutomataExamples\TrafficLight\Middleware\CycleCounterMiddleware;
use AutomataExamples\TrafficLight\Output;
use AutomataExamples\TrafficLight\States\GreenLightState;
use AutomataExamples\TrafficLight\States\RedLightState;
use AutomataExamples\TrafficLight\States\YellowLightState;
use AutomataExamples\TrafficLight\TimerInput;
use Psr\Clock\ClockInterface;

final class TrafficLightApplication
{
    private readonly ArrayContext $context;

    private readonly StateMachine $machine;

    private readonly TransitionTable $transitions;

    public function __construct(
        private readonly Output $output = new Output(),
        ClockInterface $clock = new SystemClock(),
    ) {
        $this->context = new ArrayContext(['total_ticks' => 0]);
        $this->transitions = TransitionTable::define([
            TrafficLightStatus::RED->value => [TrafficLightStatus::GREEN->value],
            TrafficLightStatus::GREEN->value => [TrafficLightStatus::YELLOW->value],
            TrafficLightStatus::YELLOW->value => [TrafficLightStatus::RED->value],
        ]);
        $this->machine = new StateMachine($this->context, transitions: $this->transitions, clock: $clock);

        $this->machine->registerMiddleware(new CycleCounterMiddleware());
        $this->machine->registerStates(new RedLightState(), new GreenLightState(), new YellowLightState());
        $this->machine->subscribe(LightColorChanged::class, new ChangeColorListener($this->output, $this->context, $clock));
        $this->machine->subscribe(TickCompleted::class, new TickReporter($this->output));
    }

    public function start(): void
    {
        $this->machine->start(TrafficLightStatus::RED->value);
    }

    public function runTicks(int $from, int $to): void
    {
        for ($tick = $from; $tick <= $to; $tick++) {
            $this->machine->tick(new TimerInput($tick));
        }
    }

    public function getMachine(): StateMachine
    {
        return $this->machine;
    }

    public function getOutput(): Output
    {
        return $this->output;
    }

    public function getTransitions(): TransitionTable
    {
        return $this->transitions;
    }
}
