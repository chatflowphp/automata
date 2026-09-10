<?php

declare(strict_types=1);

use AutomataExamples\SurveyBot\FileSnapshotStore;
use AutomataExamples\SurveyBot\SurveyBot;

require __DIR__ . '/../../vendor/autoload.php';

$directory = sys_get_temp_dir() . '/automata-survey-bot-' . getmypid();
$store = new FileSnapshotStore($directory);

echo "=== Transition graph (Mermaid) ===\n";
echo SurveyBot::transitions()->toMermaid() . "\n";

echo "=== Conversation with chat 42 (each line is a separate request) ===\n";
$conversation = ['/start', '', 'Alice', 'abc', '30', 'maybe', 'yes', 'hello?'];

foreach ($conversation as $text) {
    printf("> %s\n", $text === '' ? '(empty message)' : $text);

    // A fresh SurveyBot per request mirrors a real webhook handler: no state lives in memory.
    foreach ((new SurveyBot($store))->handle('42', $text) as $reply) {
        printf("< %s\n", $reply);
    }
}

echo "\n=== Stored snapshot for chat 42 ===\n";
$snapshot = $store->load('chat:42');
echo json_encode($snapshot, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";

$store->delete('chat:42');
@rmdir($directory);
