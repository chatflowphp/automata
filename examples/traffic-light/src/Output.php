<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight;

/**
 * Collects output lines so the example can be asserted in tests and printed in run.php.
 */
final class Output
{
    /**
     * @var list<string>
     */
    private array $lines = [];

    public function add(string $line): void
    {
        $this->lines[] = $line;
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->lines;
    }

    public function print(): void
    {
        foreach ($this->lines as $line) {
            echo $line . PHP_EOL;
        }
    }
}
