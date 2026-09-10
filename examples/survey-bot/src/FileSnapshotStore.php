<?php

declare(strict_types=1);

namespace AutomataExamples\SurveyBot;

use Automata\Exception\SnapshotHydrationException;
use Automata\Snapshot\JsonSnapshotSerializer;
use Automata\Snapshot\SnapshotSerializerInterface;
use Automata\Snapshot\SnapshotStoreInterface;
use Automata\Snapshot\StateSnapshot;
use RuntimeException;

/**
 * One JSON file per key. Good enough for a demo; use a database in production.
 */
final class FileSnapshotStore implements SnapshotStoreInterface
{
    public function __construct(
        private readonly string $directory,
        private readonly SnapshotSerializerInterface $serializer = new JsonSnapshotSerializer(),
    ) {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0o777, true) && !is_dir($this->directory)) {
            throw new RuntimeException(\sprintf('Cannot create snapshot directory "%s".', $this->directory));
        }
    }

    public function load(string $key): ?StateSnapshot
    {
        $path = $this->path($key);

        if (!is_file($path)) {
            return null;
        }

        $payload = file_get_contents($path);

        if ($payload === false) {
            throw new SnapshotHydrationException(\sprintf('Cannot read snapshot file "%s".', $path));
        }

        return $this->serializer->deserialize($payload);
    }

    public function save(string $key, StateSnapshot $snapshot): void
    {
        if (file_put_contents($this->path($key), $this->serializer->serialize($snapshot), LOCK_EX) === false) {
            throw new RuntimeException(\sprintf('Cannot write snapshot for key "%s".', $key));
        }
    }

    public function delete(string $key): void
    {
        $path = $this->path($key);

        if (is_file($path)) {
            unlink($path);
        }
    }

    private function path(string $key): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
    }
}
