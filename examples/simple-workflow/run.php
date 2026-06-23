<?php

declare(strict_types=1);

use Automata\Core\State\JsonSnapshotSerializer;
use AutomataExamples\SimpleWorkflow\AdvanceInput;
use AutomataExamples\SimpleWorkflow\Application\SimpleWorkflowApplication;
use AutomataExamples\SimpleWorkflow\States\IdleState;

require __DIR__ . '/../../vendor/autoload.php';

/**
 * @param list<string> $transitionLog
 */
function printWorkflowState(
    string $title,
    SimpleWorkflowApplication $application,
    array $transitionLog
): void {
    $orchestrator = $application->getOrchestrator();
    $context = $application->getContext();

    echo $title . PHP_EOL;
    printf("Active automaton: %s\n", $orchestrator->getActiveAutomatonId() ?? 'none');
    printf("Workflow status: %s\n", $application->workflowStatus());
    printf("Cycle count: %d\n", $application->cycleCount());

    $lastInputReason = $context->get('last_input_reason', 'none');
    printf("Last input reason: %s\n", is_string($lastInputReason) ? $lastInputReason : 'none');

    foreach ($transitionLog as $line) {
        printf("Transition log: %s\n", $line);
    }
}

$application = new SimpleWorkflowApplication();
$orchestrator = $application->getOrchestrator();
$orchestrator->activate(IdleState::ID);

printWorkflowState('=== Initial execution ===', $application, $application->transitionLog());
echo PHP_EOL;

$orchestrator->tick(new AdvanceInput('activate-workflow'));
printWorkflowState('=== After first tick ===', $application, $application->transitionLog());
echo PHP_EOL;

$serializer = new JsonSnapshotSerializer();
$snapshot = $orchestrator->snapshot();
echo "=== Snapshot JSON ===\n";
echo $serializer->serialize($snapshot) . PHP_EOL . PHP_EOL;

$restoredApplication = new SimpleWorkflowApplication();
$restoredApplication->getOrchestrator()->activateFromSnapshot($snapshot);
printWorkflowState('=== Restored execution ===', $restoredApplication, $restoredApplication->transitionLog());
