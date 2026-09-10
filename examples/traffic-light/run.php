<?php

declare(strict_types=1);

use Automata\Snapshot\JsonSnapshotSerializer;
use AutomataExamples\TrafficLight\Application\TrafficLightApplication;
use AutomataExamples\TrafficLight\Output;

require __DIR__ . '/../../vendor/autoload.php';

$output = new Output();
$serializer = new JsonSnapshotSerializer(encodeFlags: JSON_PRETTY_PRINT);

$output->add('=== Transition graph (Mermaid) ===');
$application = new TrafficLightApplication($output);
$output->add(rtrim($application->getTransitions()->toMermaid()));
$output->add('');

$output->add('=== Initial run: ticks 1-5 ===');
$application->start();
$application->runTicks(1, 5);

$payload = $serializer->serialize($application->getMachine()->snapshot());
$output->add('');
$output->add('=== Snapshot after tick 5 ===');
$output->add($payload);
$output->add('');

$output->add('=== Restored run: ticks 6-9 ===');
$restored = new TrafficLightApplication($output);
$restored->getMachine()->restore($serializer->deserialize($payload));
$restored->runTicks(6, 9);

$output->add('');
$output->add('Simulation complete.');
$output->print();
