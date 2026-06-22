<?php

declare(strict_types=1);

namespace Automata\Contracts;

use Automata\DTO\StateSnapshot;

interface SnapshotSerializerInterface
{
    public function serialize(StateSnapshot $snapshot): string;

    public function deserialize(string $payload): StateSnapshot;
}
