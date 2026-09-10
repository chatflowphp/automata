<?php

declare(strict_types=1);

use Automata\Snapshot\JsonSnapshotSerializer;
use AutomataExamples\SimpleWorkflow\AdvanceInput;
use AutomataExamples\SimpleWorkflow\Application\SimpleWorkflowApplication;
use AutomataExamples\SimpleWorkflow\States\IdleState;

require __DIR__ . '/../../vendor/autoload.php';

function printWorkflowState(string $title, SimpleWorkflowApplication $application): void
{
    $machine = $application->getMachine();
    $context = $application->getContext();

    echo $title . PHP_EOL;
    printf("Current state: %s\n", $machine->getCurrentStateId() ?? 'none');
    printf("Workflow status: %s\n", $application->workflowStatus());
    printf("Cycle count: %d\n", $application->cycleCount());
    printf("Last input reason: %s\n", $context->getString('last_input_reason', 'none'));

    foreach ($application->transitionLog() as $line) {
        printf("Transition log: %s\n", $line);
    }
}

$application = new SimpleWorkflowApplication();
$machine = $application->getMachine();
$machine->start(IdleState::ID);

printWorkflowState('=== Initial execution ===', $application);
echo PHP_EOL;

$result = $machine->tick(new AdvanceInput('activate-workflow'));
printWorkflowState('=== After first tick ===', $application);
printf("Transitioned: %s (%s -> %s)\n", $result->transitioned() ? 'yes' : 'no', $result->fromStateId, $result->toStateId);
echo PHP_EOL;

$serializer = new JsonSnapshotSerializer(encodeFlags: JSON_PRETTY_PRINT);
$snapshot = $machine->snapshot();
echo "=== Snapshot JSON ===\n";
echo $serializer->serialize($snapshot) . PHP_EOL . PHP_EOL;

$restoredApplication = new SimpleWorkflowApplication();
$restoredApplication->getMachine()->restore($serializer->deserialize($serializer->serialize($snapshot)));
printWorkflowState('=== Restored execution ===', $restoredApplication);
