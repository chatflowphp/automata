<?php

declare(strict_types=1);

namespace Automata\Core;

use Automata\Contracts\AutomatonInterface;
use Automata\Contracts\AutomatonRegistryInterface;
use Automata\Exceptions\AutomatonNotFoundException;

/**
 * Lightweight registry for storing automata instances keyed by their identifiers.
 *
 * Replaces the previous Symfony-based container, keeping the public API expected by the
 * orchestrator while avoiding a dependency on the Symfony DI component.
 */
final class AutomatonRegistry implements AutomatonRegistryInterface
{
    /**
     * @var array<string, AutomatonInterface>
     */
    private array $automata = [];

    public function register(AutomatonInterface $automaton): void
    {
        $this->automata[$automaton->getId()] = $automaton;
    }

    /**
     * @throws AutomatonNotFoundException When the requested identifier is not registered.
     */
    public function getAutomaton(string $id): AutomatonInterface
    {
        if (!isset($this->automata[$id])) {
            throw new AutomatonNotFoundException(sprintf('Automaton "%s" is not registered.', $id));
        }

        return $this->automata[$id];
    }

    public function hasAutomaton(string $id): bool
    {
        return isset($this->automata[$id]);
    }

    /**
     * @return array<string, AutomatonInterface>
     */
    public function all(): array
    {
        return $this->automata;
    }
}
