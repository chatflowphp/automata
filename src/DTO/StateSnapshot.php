<?php

declare(strict_types=1);

namespace Automata\DTO;

use Automata\Core\State\StateNormalizer;
use Automata\Exceptions\SnapshotHydrationException;
use JsonSerializable;

/**
 * Serializable DTO capturing the orchestrator runtime state.
 */
final class StateSnapshot implements JsonSerializable
{
    /**
     * @param array<string, mixed> $contextState
     * @param array<string, array<string, mixed>> $automataStates
     */
    public function __construct(
        public readonly array $contextState,
        public readonly ?string $fsmAutomatonId,
        public readonly array $automataStates
    ) {
        if ($this->fsmAutomatonId !== null && $this->fsmAutomatonId === '') {
            throw new SnapshotHydrationException('Invalid snapshot: active automaton id must be a non-empty string or null.');
        }
    }

    /**
     * @return array{
     *     contextState: array<string, mixed>,
     *     fsmAutomatonId: string|null,
     *     automataStates: array<string, array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        $contextState = self::normalizeStateArray($this->contextState, 'contextState');
        $automataStates = [];

        foreach ($this->automataStates as $automatonId => $state) {
            if ($automatonId === '') {
                throw new SnapshotHydrationException('Invalid snapshot: automataStates keys must be non-empty strings.');
            }

            $automataStates[$automatonId] = self::normalizeStateArray($state, sprintf('automataStates.%s', $automatonId));
        }

        return [
            'contextState' => $contextState,
            'fsmAutomatonId' => $this->fsmAutomatonId,
            'automataStates' => $automataStates,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (!array_key_exists('contextState', $data) || !is_array($data['contextState'])) {
            throw new SnapshotHydrationException('Invalid snapshot: "contextState" must be present and must be an array.');
        }

        if (!array_key_exists('fsmAutomatonId', $data) || (!is_string($data['fsmAutomatonId']) && $data['fsmAutomatonId'] !== null)) {
            throw new SnapshotHydrationException('Invalid snapshot: "fsmAutomatonId" must be a string or null.');
        }

        if (!array_key_exists('automataStates', $data) || !is_array($data['automataStates'])) {
            throw new SnapshotHydrationException('Invalid snapshot: "automataStates" must be present and must be an array.');
        }

        return new self(
            self::normalizeStateArray($data['contextState'], 'contextState'),
            $data['fsmAutomatonId'],
            self::normalizeAutomataStates($data['automataStates'])
        );
    }

    /**
     * @return array{
     *     contextState: array<string, mixed>,
     *     fsmAutomatonId: string|null,
     *     automataStates: array<string, array<string, mixed>>
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array<array-key, mixed> $state
     *
     * @return array<string, mixed>
     */
    private static function normalizeStateArray(array $state, string $path): array
    {
        $normalized = StateNormalizer::normalizeArray($state, $path);

        foreach (array_keys($normalized) as $key) {
            if (!is_string($key)) {
                throw new SnapshotHydrationException(sprintf(
                    'Invalid snapshot: "%s" must use string keys.',
                    $path
                ));
            }
        }

        /** @var array<string, mixed> $normalized */
        return $normalized;
    }

    /**
     * @param array<array-key, mixed> $automataStates
     *
     * @return array<string, array<string, mixed>>
     */
    private static function normalizeAutomataStates(array $automataStates): array
    {
        $normalized = [];

        foreach ($automataStates as $automatonId => $state) {
            if (!is_string($automatonId) || $automatonId === '') {
                throw new SnapshotHydrationException('Invalid snapshot: automataStates keys must be non-empty strings.');
            }

            if (!is_array($state)) {
                throw new SnapshotHydrationException(sprintf(
                    'Invalid snapshot: state for automaton "%s" must be an array.',
                    $automatonId
                ));
            }

            $normalized[$automatonId] = self::normalizeStateArray($state, sprintf('automataStates.%s', $automatonId));
        }

        return $normalized;
    }
}
