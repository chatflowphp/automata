<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\Application;

use Automata\Core\Orchestrator;
use Automata\Events\CycleCompletedEvent;
use AutomataExamples\TrafficLight\Enum\TrafficLightStatus;
use AutomataExamples\TrafficLight\Event\LightColorChangedEvent;
use AutomataExamples\TrafficLight\Listener\ChangeColorListener;
use AutomataExamples\TrafficLight\Listener\CycleResponseListener;
use AutomataExamples\TrafficLight\Middleware\CycleCounterMiddleware;
use AutomataExamples\TrafficLight\States\GreenLightState;
use AutomataExamples\TrafficLight\States\RedLightState;
use AutomataExamples\TrafficLight\States\YellowLightState;
use AutomataExamples\TrafficLight\TimerInput;
use AutomataExamples\TrafficLight\TrafficLightContext;

final class TrafficLightApplication
{
    private TrafficLightContext $context;
    private Orchestrator $orchestrator;
    private ResponseSimple $response;

    public function __construct(?ResponseSimple $response = null)
    {
        $this->response = $response ?? new ResponseSimple();
        $this->context = new TrafficLightContext();
        $this->context->set('total_ticks', 0);
        $this->orchestrator = new Orchestrator($this->context);

        $this->registerAutomata();
        $this->registerListeners();
    }

    public function run(int $ticks): void
    {
        $this->orchestrator->activate(TrafficLightStatus::RED->value);
        for ($tick = 0; $tick <= $ticks; $tick++) {
            $this->orchestrator->tick(new TimerInput($tick));
        }
    }

    public function getContext(): TrafficLightContext
    {
        return $this->context;
    }

    public function getOrchestrator(): Orchestrator
    {
        return $this->orchestrator;
    }

    private function registerAutomata(): void
    {
        $this->orchestrator->registerMiddleware(new CycleCounterMiddleware());
        $this->orchestrator->registerAutomaton(new RedLightState());
        $this->orchestrator->registerAutomaton(new GreenLightState());
        $this->orchestrator->registerAutomaton(new YellowLightState());
    }

    private function registerListeners(): void
    {
        $this->orchestrator->subscribe(
            LightColorChangedEvent::NAME,
            new ChangeColorListener($this->response, $this->context)
        );
        $this->orchestrator->subscribe(
            CycleCompletedEvent::NAME,
            new CycleResponseListener($this->response)
        );
    }
}
