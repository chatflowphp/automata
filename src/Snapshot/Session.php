<?php

declare(strict_types=1);

namespace Automata\Snapshot;

use Automata\Exception\IllegalStateException;
use Automata\Machine\InputInterface;
use Automata\Machine\StateMachine;
use Automata\Machine\TickResult;

/**
 * One persisted run of a state machine, identified by a store key.
 *
 * Typical request handling: resume(), tick() with the incoming input, persist(). A fresh machine is
 * built per request by the factory; the snapshot in the store carries everything between requests.
 */
final class Session
{
    private function __construct(
        private readonly StateMachine $machine,
        private readonly SnapshotStoreInterface $store,
        private readonly string $key,
        private readonly bool $new,
    ) {}

    /**
     * Loads the snapshot stored under the key into a machine built by the factory. When no snapshot
     * exists the machine is started in the initial state and the session reports isNew().
     *
     * @param callable(): StateMachine $factory Must return a machine that is not started.
     */
    public static function resume(
        SnapshotStoreInterface $store,
        string $key,
        callable $factory,
        string $initialStateId,
    ): self {
        $machine = $factory();

        if ($machine->isStarted()) {
            throw new IllegalStateException('Session factory must return a state machine that is not started.');
        }

        $snapshot = $store->load($key);

        if ($snapshot === null) {
            $machine->start($initialStateId);

            return new self($machine, $store, $key, true);
        }

        $machine->restore($snapshot);

        return new self($machine, $store, $key, false);
    }

    public function isNew(): bool
    {
        return $this->new;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getMachine(): StateMachine
    {
        return $this->machine;
    }

    public function tick(InputInterface $input): TickResult
    {
        return $this->machine->tick($input);
    }

    public function persist(): void
    {
        $this->store->save($this->key, $this->machine->snapshot());
    }

    public function end(): void
    {
        $this->store->delete($this->key);
    }
}
