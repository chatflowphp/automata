<?php

declare(strict_types=1);

namespace Automata\Machine\Transition;

use Automata\Context\ContextInterface;
use Closure;
use InvalidArgumentException;

/**
 * Explicit transition graph with optional guards.
 *
 * Define it declaratively:
 *
 *     TransitionTable::define([
 *         'ask_name' => ['ask_age'],
 *         'ask_age'  => ['confirm' => fn (ContextInterface $c): bool => $c->getInt('age') > 0],
 *         'confirm'  => ['done', 'ask_name'],
 *     ]);
 */
final class TransitionTable implements TransitionPolicyInterface
{
    /**
     * @var array<string, array<string, Closure(ContextInterface): bool|null>>
     */
    private array $edges = [];

    /**
     * @param array<string, array<array-key, string|callable(ContextInterface): bool>> $definition
     */
    public static function define(array $definition): self
    {
        $table = new self();

        foreach ($definition as $from => $targets) {
            foreach ($targets as $key => $value) {
                if (\is_string($value)) {
                    $table->allow($from, $value);

                    continue;
                }

                if (\is_string($key)) {
                    $table->allow($from, $key, $value);

                    continue;
                }

                throw new InvalidArgumentException(\sprintf(
                    'Transition definition for "%s" must list target ids or map target id => guard.',
                    $from,
                ));
            }
        }

        return $table;
    }

    /**
     * @param (callable(ContextInterface): bool)|null $guard
     */
    public function allow(string $fromStateId, string $toStateId, ?callable $guard = null): self
    {
        $this->edges[$fromStateId][$toStateId] = $guard === null ? null : Closure::fromCallable($guard);

        return $this;
    }

    public function isAllowed(string $fromStateId, string $toStateId, ContextInterface $context): bool
    {
        if (!\array_key_exists($toStateId, $this->edges[$fromStateId] ?? [])) {
            return false;
        }

        $guard = $this->edges[$fromStateId][$toStateId];

        return $guard === null || $guard($context);
    }

    /**
     * Targets declared for a state, ignoring guards.
     *
     * @return list<string>
     */
    public function targetsFrom(string $fromStateId): array
    {
        return array_keys($this->edges[$fromStateId] ?? []);
    }

    /**
     * Whether the edge exists and carries a guard.
     */
    public function isGuarded(string $fromStateId, string $toStateId): bool
    {
        return ($this->edges[$fromStateId][$toStateId] ?? null) !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function edges(): array
    {
        $edges = [];

        foreach ($this->edges as $from => $targets) {
            $edges[$from] = array_keys($targets);
        }

        return $edges;
    }

    /**
     * Renders the graph as a Mermaid state diagram.
     */
    public function toMermaid(): string
    {
        $ids = [];

        foreach ($this->edges as $from => $targets) {
            $ids[$from] = true;

            foreach (array_keys($targets) as $to) {
                $ids[$to] = true;
            }
        }

        $lines = ['stateDiagram-v2'];

        foreach (array_keys($ids) as $id) {
            $lines[] = \sprintf('    state "%s" as %s', $id, self::mermaidAlias($id));
        }

        foreach ($this->edges as $from => $targets) {
            foreach ($targets as $to => $guard) {
                $lines[] = \sprintf(
                    '    %s --> %s%s',
                    self::mermaidAlias($from),
                    self::mermaidAlias($to),
                    $guard === null ? '' : ' : guarded',
                );
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private static function mermaidAlias(string $stateId): string
    {
        return (string) preg_replace('/[^A-Za-z0-9_]/', '_', $stateId);
    }
}
