<?php

declare(strict_types=1);

namespace AutomataExamples\TrafficLight\Application;

final class ResponseSimple
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

    public function output(): void
    {
        foreach ($this->lines as $line) {
            echo $line === '' ? PHP_EOL : $line . PHP_EOL;
        }
    }
}
