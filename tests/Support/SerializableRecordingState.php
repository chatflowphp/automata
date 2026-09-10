<?php

declare(strict_types=1);

namespace Automata\Tests\Support;

use Automata\State\SerializableStateInterface;

final class SerializableRecordingState extends RecordingState implements SerializableStateInterface
{
    /**
     * @var array<string, mixed>
     */
    public array $state = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $restoredState = null;

    public function getState(): array
    {
        return $this->state;
    }

    public function setState(array $state): void
    {
        $this->state = $state;
        $this->restoredState = $state;
    }
}
