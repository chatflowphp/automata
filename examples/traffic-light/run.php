<?php

declare(strict_types=1);

use AutomataExamples\TrafficLight\Application\ResponseSimple;
use AutomataExamples\TrafficLight\Application\TrafficLightApplication;
use AutomataExamples\TrafficLight\Enum\TrafficLightStatus;
use AutomataExamples\TrafficLight\TimerInput;

require __DIR__ . '/../../vendor/autoload.php';

$response = new ResponseSimple();

$totalTicks = 8;
$saveAfterTick = 4;

$response->add('=== Initial execution ===');
$application = new TrafficLightApplication($response);
$orchestrator = $application->getOrchestrator();

$orchestrator->activate(TrafficLightStatus::RED->value);
for ($tick = 0; $tick <= $saveAfterTick; $tick++) {
    $orchestrator->tick(new TimerInput($tick));
}

$snapshot = $orchestrator->snapshot();
$response->add('');
$response->add(sprintf(
    "=== Snapshot saved after tick %d (active automaton: %s) ===",
    $saveAfterTick,
    $snapshot->fsmAutomatonId ?? 'unknown'
));
$response->add('');
$response->add('=== Restored execution ===');

$restoredApplication = new TrafficLightApplication($response);
$restoredOrchestrator = $restoredApplication->getOrchestrator();
$restoredOrchestrator->activateFromSnapshot($snapshot);

$resumeTick = $saveAfterTick + 1;
for ($tick = $resumeTick; $tick <= $totalTicks; $tick++) {
    $restoredOrchestrator->tick(new TimerInput($tick));
}

$response->add('');
$response->add('Simulation complete.');

$response->output();
